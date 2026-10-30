<?php

namespace App\Models;

use App\Enums\ClaimResult;
use Database\Factories\PrizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'is_miss'])]
class Prize extends Model
{
    /** @use HasFactory<PrizeFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Prize $prize): void {
            $prize->token ??= Str::random(40);
            $prize->number ??= (int) static::query()->where('event_id', $prize->event_id)->max('number') + 1;
        });
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * まだ誰にも取られていない当たりの景品かどうか。ハズレは在庫として扱わない。
     */
    public function isAvailable(): bool
    {
        return ! $this->is_miss && $this->participant_id === null;
    }

    /**
     * 参加者がこの景品を取得しようとした結果を返す。
     * 同時に読み取られても1人だけが取得できるよう、未取得の行だけを条件付きで更新する。
     * 獲得上限の確認と取得は、参加者の行をロックした1つのトランザクションで行い、同時に読み取っても上限を超えないようにする。
     */
    public function claimFor(Participant $participant): ClaimResult
    {
        if ($this->participant_id === $participant->id) {
            return ClaimResult::AlreadyMine;
        }

        $result = DB::transaction(function () use ($participant): ClaimResult {
            $lockedParticipant = Participant::query()->whereKey($participant->id)->lockForUpdate()->firstOrFail();

            if ($lockedParticipant->hasReachedPrizeLimit()) {
                return ClaimResult::LimitReached;
            }

            if ($this->is_miss) {
                if ($lockedParticipant->event->count_misses_toward_limit) {
                    $lockedParticipant->foundMisses()->syncWithoutDetaching([$this->id]);
                }

                return ClaimResult::Miss;
            }

            $claimed = static::query()
                ->whereKey($this->id)
                ->whereNull('participant_id')
                ->update(['participant_id' => $participant->id, 'acquired_at' => now()]);

            return $claimed === 1 ? ClaimResult::Won : ClaimResult::TakenByOther;
        });

        $this->refresh();

        return $result;
    }

    /**
     * 景品一覧の絞り込み。「残り」を選ぶと、まだ獲得されていない当たり景品だけにする。
     *
     * @param  Builder<Prize>  $query
     * @param  array{stock?: string|null}  $filters
     */
    #[Scope]
    protected function search(Builder $query, array $filters): void
    {
        $query->when(($filters['stock'] ?? null) === 'available', fn (Builder $q) => $q->where('is_miss', false)->whereNull('participant_id'));
    }

    /**
     * 参加者が受付で景品と交換済みかどうか。
     */
    public function isRedeemed(): bool
    {
        return $this->redeemed_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_miss' => 'boolean',
            'acquired_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }
}
