<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

/**
 * イベントにゲストとして参加した人。パスワードは持たず、セッションだけでログイン状態を保つ。
 */
#[Fillable(['nickname'])]
#[Hidden(['remember_token', 'redeem_token'])]
class Participant extends Authenticatable
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Participant $participant): void {
            $participant->redeem_token ??= Str::random(40);
        });

        // 宝探しをやめた参加者の景品は在庫に戻るので、獲得・引き換えの記録も消す
        static::deleting(function (Participant $participant): void {
            $participant->prizes()->update(['acquired_at' => null, 'redeemed_at' => null]);
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
     * @return HasMany<Prize, $this>
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class);
    }

    /**
     * この参加者が開けたハズレ（同じハズレは1回だけ記録される）。
     *
     * @return BelongsToMany<Prize, $this>
     */
    public function foundMisses(): BelongsToMany
    {
        return $this->belongsToMany(Prize::class, 'participant_misses')->withTimestamps();
    }

    /**
     * 獲得した景品のうち、まだ引き換えていない個数。
     */
    public function unredeemedPrizeCount(): int
    {
        return $this->prizes()->whereNull('redeemed_at')->count();
    }

    /**
     * 獲得した景品をすべて引き換え済みにし、引き換えた個数を返す。
     */
    public function redeemAllPrizes(): int
    {
        return $this->prizes()->whereNull('redeemed_at')->update(['redeemed_at' => now()]);
    }

    /**
     * ニックネームが同じ参加者を見分けるための参加者ID（例：#0012）。
     */
    public function displayId(): string
    {
        return '#'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 獲得上限に数える個数。イベントの設定によってはハズレも含める。
     */
    public function countedPrizeCount(): int
    {
        $count = $this->prizes()->count();

        if ($this->event->count_misses_toward_limit) {
            $count += $this->foundMisses()->count();
        }

        return $count;
    }

    /**
     * あと何個景品を獲得できるか。上限なしのイベントでは null。
     */
    public function remainingPrizeAllowance(): ?int
    {
        $limit = $this->event->max_prizes_per_participant;

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->countedPrizeCount());
    }

    /**
     * 獲得上限に達していて、これ以上QRコードを読み取れないかどうか。
     */
    public function hasReachedPrizeLimit(): bool
    {
        return $this->remainingPrizeAllowance() === 0;
    }

    public function getAuthPassword(): string
    {
        return '';
    }
}
