<?php

namespace App\Models;

use App\Enums\TimerState;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit', 'time_limit_minutes', 'scheduled_start_at', 'scheduled_minutes'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * 削除したイベントは、30日間は「最近削除したイベント」から戻せる。
     * そのあいだ、景品のQR・参加用QR・参加者カード・参加者のログインは使えない（Prize と Participant の activeEvent）。
     */
    use SoftDeletes;

    /** 削除したイベントを戻せる日数 */
    public const TRASH_DAYS = 30;

    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            $event->join_token ??= Str::random(40);
            // これから作るイベントは、本番の前に開催予定が必要（前からあるイベントは false のまま）
            $event->requires_schedule ??= true;
        });
    }

    /**
     * 削除してから30日たったイベントを、景品・参加者ごと本当に消す。消した件数を返す。
     */
    public static function purgeExpiredTrash(): int
    {
        $expired = static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(self::TRASH_DAYS))->get();
        $expired->each->forceDelete();

        return $expired->count();
    }

    /**
     * 完全に消える日（削除したイベントのとき）。
     */
    public function purgeAt(): ?Carbon
    {
        return $this->deleted_at?->copy()->addDays(self::TRASH_DAYS);
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
        // 引き継ぐのは、このイベントに登録できる数まで（No.の小さい順）
        $limit = $this->prizeLimit();
        $prizes = $source->prizes()->orderBy('number')
            ->when($limit !== null, fn (Builder $query) => $query->limit(max(0, $limit - $this->prizes()->count())))
            ->get();

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
            $this->forceFill(['started_at' => null, 'ends_at' => null, 'prize_list_printed_at' => null, 'trial_mode' => false])->save();

            return $this->participants()->delete();
        });
    }

    /**
     * このイベントに登録できるお宝QR（景品）の数。当たりもハズレも数える。null は上限なし。
     */
    public function prizeLimit(): ?int
    {
        return $this->user !== null ? $this->user->prizeLimit() : User::FREE_PRIZE_LIMIT;
    }

    /**
     * お宝QR（景品）を、もう追加できないかどうか。
     */
    public function isPrizeFull(): bool
    {
        $limit = $this->prizeLimit();

        return $limit !== null && $this->prizes()->count() >= $limit;
    }

    /**
     * お試しモードで参加できる人数（参加者カードも含む）。
     */
    public const TRIAL_PARTICIPANT_LIMIT = 3;

    /**
     * お試しモードを始められるか。本番の参加者や記録が混ざらないよう、まだ誰も参加していないときだけ。
     */
    public function canStartTrial(): bool
    {
        return ! $this->trial_mode && ! $this->participants()->exists() && ! $this->isTimerUsed();
    }

    /**
     * 宝探しタイマーをスタートしたあとか（動いている、または時間切れ）。
     * このあいだはお試しモードを始めない（本番のつもりのタイマーが、お試しの中で進んだり、お試しの終わりに消えたりしないように）。
     */
    public function isTimerUsed(): bool
    {
        return $this->started_at !== null || $this->ends_at !== null;
    }

    public function startTrial(): void
    {
        $this->forceFill(['trial_mode' => true])->save();
    }

    /**
     * お試しを終えて本番にする。お試しの参加者・獲得の記録を消し、タイマーをリセットする（景品とQR、制限時間の設定、印刷した景品リストはそのまま）。
     */
    public function endTrial(): void
    {
        DB::transaction(function (): void {
            $this->resetAcquisitions();
            $this->participants()->delete();
            $this->forceFill(['started_at' => null, 'ends_at' => null, 'trial_mode' => false])->save();
        });
    }

    /**
     * 参加者カードを作る。参加できる人数の残りまで（participantLimit）。作った枚数を返す。
     *
     * @param  list<string>  $names
     */
    public function createCards(array $names): int
    {
        return DB::transaction(function () use ($names): int {
            $locked = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            $limit = $locked->participantLimit();
            if ($limit !== null) {
                $room = max(0, $limit - $locked->participants()->count());
                $names = array_slice($names, 0, $room);
            }

            foreach ($names as $name) {
                $card = $locked->participants()->make(['nickname' => $name]);
                $card->card_token = Str::random(40);
                $card->save();
            }

            return count($names);
        });
    }

    /**
     * 参加できる人数（参加者カードも含む）。お試しモード中は3人。それ以外は主催者の区分で決まる（null は上限なし）。
     */
    public function participantLimit(): ?int
    {
        // 主催者が見つからないときは、いちばん小さい人数にする（null は「上限なし」なので ?? は使わない）
        $ownerLimit = $this->user !== null ? $this->user->participantLimit() : User::FREE_PARTICIPANT_LIMIT;

        if ($this->trial_mode) {
            return $ownerLimit === null ? self::TRIAL_PARTICIPANT_LIMIT : min(self::TRIAL_PARTICIPANT_LIMIT, $ownerLimit);
        }

        return $ownerLimit;
    }

    /**
     * もう参加できる人数に達しているか。
     */
    public function isFull(): bool
    {
        $limit = $this->participantLimit();

        return $limit !== null && $this->participants()->count() >= $limit;
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
     * 景品リストを印刷できたことを記録する（主催者が「印刷できた」を押したとき）。
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
     * 「控えを保存」の画像に入れる、全部の景品の獲得状況。at はこの状態を読んだ時刻（日本時間）。
     *
     * @return array{event: string, phase: string, schedule: string|null, at: string, prizes: list<array<string, mixed>>}
     */
    public function snapshotData(): array
    {
        return [
            'event' => $this->name,
            'phase' => $this->phase() === 'live' ? '開催中' : ($this->phase() === 'closed' ? '締切後' : ''),
            'schedule' => $this->scheduleLabel(),
            'at' => now()->toIso8601String(),
            'prizes' => $this->prizes()->with('participant')->orderBy('number')->get()->map(fn (Prize $prize) => [
                'no' => $prize->number,
                'name' => $prize->name,
                'kind' => $prize->is_miss ? 'ハズレ' : '当たり',
                'status' => $prize->statusLabel(),
                'winner' => $prize->participant?->nickname,
                'winnerId' => $prize->participant?->displayId(),
                'at' => $prize->acquired_at?->format('H:i'),
            ])->values()->all(),
        ];
    }

    /**
     * 開催予定の「開催時間」の選択肢（分 => 表示）。
     *
     * @var array<int, string>
     */
    public const SCHEDULE_LENGTHS = [
        30 => '30分',
        60 => '1時間',
        90 => '1時間30分',
        120 => '2時間',
        150 => '2時間30分',
        180 => '3時間',
        240 => '4時間',
        300 => '5時間',
        360 => '6時間',
        480 => '8時間',
    ];

    /**
     * 開催予定が入っているか。
     */
    public function hasSchedule(): bool
    {
        return $this->scheduled_start_at !== null && $this->scheduled_minutes !== null;
    }

    public function scheduledEndAt(): ?Carbon
    {
        return $this->hasSchedule() ? $this->scheduled_start_at->copy()->addMinutes($this->scheduled_minutes) : null;
    }

    /**
     * 本番（宝探しスタート、お試しでない参加の受付）を始めるのに、開催予定が足りないか。お試しモードは予定なしで使える。
     */
    public function needsScheduleToStart(): bool
    {
        return $this->requires_schedule && ! $this->hasSchedule() && ! $this->trial_mode;
    }

    /**
     * 「11月29日（日）10:00〜12:00」の形。日をまたぐときは終わりにも日付を付ける。
     */
    public function scheduleLabel(): ?string
    {
        if (! $this->hasSchedule()) {
            return null;
        }

        $start = $this->scheduled_start_at;
        $end = $this->scheduledEndAt();
        $weekday = ['日', '月', '火', '水', '木', '金', '土'];
        $day = fn (Carbon $at) => $at->format('n月j日').'（'.$weekday[$at->dayOfWeek].'）';

        return $day($start).$start->format('H:i').'〜'.($end->isSameDay($start) ? '' : $day($end)).$end->format('H:i');
    }

    /**
     * 主催者の画面で、どの操作を先に見せるかを決めるための、イベントの段階。
     *   trial  … お試しモード中
     *   prep   … 準備中（まだ誰も参加していなくて、タイマーも動かしていない）
     *   live   … 開催中（タイマーが動いている、または参加者がいる）
     *   closed … 宝探しの締切後（タイマーが終わった。ここからは景品の引き換え）
     * 状態を新しく持つのではなく、今ある trial_mode・タイマー・参加者の数から決める。
     */
    public function phase(): string
    {
        if ($this->trial_mode) {
            return 'trial';
        }

        return match ($this->timerState()) {
            TimerState::Ended => 'closed',
            TimerState::Running => 'live',
            default => ($this->participants_count ?? $this->participants()->count()) > 0 ? 'live' : 'prep',
        };
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
            'trial_mode' => 'boolean',
            'scheduled_start_at' => 'datetime',
            'scheduled_minutes' => 'integer',
            'requires_schedule' => 'boolean',
        ];
    }
}
