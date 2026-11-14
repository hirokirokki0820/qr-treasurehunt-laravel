<?php

namespace App\Models;

use App\Enums\TimerState;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit', 'time_limit_minutes'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            $event->join_token ??= Str::random(40);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Prize, $this>
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class);
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * このイベントで、2人以上が使っているニックネームの一覧。
     *
     * @return list<string>
     */
    public function duplicatedNicknames(): array
    {
        return $this->participants()
            ->select('nickname')
            ->groupBy('nickname')
            ->havingRaw('count(*) > 1')
            ->pluck('nickname')
            ->all();
    }

    /**
     * 別のイベントの景品（No.・景品名・メッセージ・ハズレ設定）をこのイベントに複製する。
     * 複製した景品は未取得の状態で、QRコード用のトークンも新しく発行される。
     */
    public function copyPrizesFrom(Event $source): int
    {
        $prizes = $source->prizes()->orderBy('number')->get();

        foreach ($prizes as $prize) {
            $copy = new Prize($prize->only(['name', 'description', 'is_miss']));
            $copy->number = $prize->number;
            $this->prizes()->save($copy);
        }

        return $prizes->count();
    }

    /**
     * すべての景品を未取得（在庫あり）に戻し、リセットした件数を返す。引き換えの記録も消す。
     */
    public function resetAcquisitions(): int
    {
        DB::table('participant_misses')
            ->whereIn('prize_id', $this->prizes()->select('id'))
            ->delete();

        return $this->prizes()
            ->whereNotNull('participant_id')
            ->update(['participant_id' => null, 'acquired_at' => null, 'redeemed_at' => null]);
    }

    /**
     * イベントを終了する。景品の獲得状況をリセットし、参加者を全員削除する。
     * 参加者のレコードがなくなるため、ログイン中の参加者は次の操作で強制的にログアウト扱いになる。
     *
     * @return int 削除（ログアウト）した参加者の人数
     */
    public function end(): int
    {
        return DB::transaction(function (): int {
            $this->resetAcquisitions();
            // 紙の景品リストは「渡した人」を書き込んで使うので、次の回の前に印刷し直してもらう
            $this->forceFill(['started_at' => null, 'ends_at' => null, 'prize_list_printed_at' => null])->save();

            return $this->participants()->delete();
        });
    }

    /**
     * 緊急時に紙で続けるための景品リストを、印刷し(直し)てもらう必要があるかどうか。
     * 景品があるのにまだ印刷ページを開いていないとき、または開いたあとに景品を足したときに true。
     */
    public function needsPrizeListPrint(): bool
    {
        if ($this->prize_list_printed_at === null) {
            return $this->prizes()->exists();
        }

        return $this->prizes()->where('created_at', '>', $this->prize_list_printed_at)->exists();
    }

    /**
     * 景品リストの印刷ページを開いたことを記録する。
     */
    public function markPrizeListPrinted(): void
    {
        $this->forceFill(['prize_list_printed_at' => now()])->save();
    }

    /**
     * 制限時間が設定されているかどうか。
     */
    public function hasTimeLimit(): bool
    {
        return $this->time_limit_minutes !== null;
    }

    /**
     * 宝探しタイマーの今の状態。
     */
    public function timerState(): TimerState
    {
        return match (true) {
            ! $this->hasTimeLimit() => TimerState::None,
            $this->ends_at === null => TimerState::Waiting,
            $this->ends_at->isFuture() => TimerState::Running,
            default => TimerState::Ended,
        };
    }

    /**
     * 宝探し中なら、終了までの残り秒数。それ以外は null。
     */
    public function remainingSeconds(): ?int
    {
        if ($this->timerState() !== TimerState::Running) {
            return null;
        }

        return max(0, (int) ceil(now()->diffInSeconds($this->ends_at, false)));
    }

    /**
     * 宝探しをスタートする（終了後なら、もう一度スタートする）。
     */
    public function startTimer(): void
    {
        $this->forceFill([
            'started_at' => now(),
            'ends_at' => now()->addMinutes($this->time_limit_minutes),
        ])->save();
    }

    /**
     * 宝探し中に、終了時刻を延ばす。
     */
    public function extendTimer(int $minutes = 5): void
    {
        if ($this->timerState() === TimerState::Running) {
            $this->forceFill(['ends_at' => $this->ends_at->addMinutes($minutes)])->save();
        }
    }

    /**
     * 宝探し中に、今すぐ締め切る。
     */
    public function stopTimer(): void
    {
        if ($this->timerState() === TimerState::Running) {
            $this->forceFill(['ends_at' => now()])->save();
        }
    }

    /**
     * タイマーをスタート前に戻す（主催者が間違えてスタートしたときの救済）。獲得した景品はそのまま残す。
     * 参加者の画面は、状態の問い合わせで変化に気づいて読み込み直し、「まもなく宝探しスタート！」に戻る。
     */
    public function resetTimer(): void
    {
        $this->forceFill(['started_at' => null, 'ends_at' => null])->save();
    }

    /**
     * 参加者の画面のポーリング用。状態・残り秒数と、変化を見分けるための目印。
     *
     * 宝探し中の延長では状態（state）が変わらないので、画面は再読み込みせずに残り時間と終了時刻だけを差し替える。
     *
     * 端末どうしで表示をそろえるため、終了時刻（ends_ms）とサーバーの今の時刻（now_ms）をミリ秒で返す。
     * 画面はこの2つと、問い合わせにかかった時間から、端末の時計とのずれを見積もって数える。
     *
     * @return array{state: string, remaining: int|null, total: int|null, ends: string|null, ends_ms: int|null, now_ms: int, version: string}
     */
    public function timerStatus(): array
    {
        $state = $this->timerState();

        return [
            'state' => $state->value,
            'remaining' => $this->remainingSeconds(),
            'total' => $this->started_at && $this->ends_at ? max(1, (int) $this->started_at->diffInSeconds($this->ends_at)) : null,
            'ends' => $this->ends_at?->format('H:i'),
            'ends_ms' => $state === TimerState::Running ? $this->ends_at->getTimestampMs() : null,
            'now_ms' => now()->getTimestampMs(),
            'version' => $state->value.':'.($this->ends_at?->getTimestamp() ?? 0),
        ];
    }

    /**
     * 参加者1人あたりの獲得上限が設定されているかどうか。
     */
    public function hasPrizeLimit(): bool
    {
        return $this->max_prizes_per_participant !== null;
    }

    /**
     * 「1人3個まで（ハズレも含む）」のような、獲得上限の説明文。上限なしなら null。
     */
    public function prizeLimitLabel(): ?string
    {
        if (! $this->hasPrizeLimit()) {
            return null;
        }

        return '1人'.$this->max_prizes_per_participant.'個まで（'.($this->count_misses_toward_limit ? 'ハズレも含む' : 'ハズレは含まない').'）';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_prizes_per_participant' => 'integer',
            'count_misses_toward_limit' => 'boolean',
            'time_limit_minutes' => 'integer',
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'prize_list_printed_at' => 'datetime',
        ];
    }
}
