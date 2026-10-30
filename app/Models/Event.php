<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit'])]
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

            return $this->participants()->delete();
        });
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
        ];
    }
}
