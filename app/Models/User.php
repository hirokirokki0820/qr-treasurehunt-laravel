<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * 宝探しイベントの主催者。「kobapp でログイン」で入り、kobapp のユーザーID（kobapp_user_id）で見分ける。
 */
#[Fillable(['kobapp_user_id', 'name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * 本番を開いた記録。
     *
     * @return HasMany<EventRun, $this>
     */
    public function eventRuns(): HasMany
    {
        return $this->hasMany(EventRun::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * 利用の区分（kobapp.jp から受け取る）。参加できる人数は participantLimit() で決まる。
     *
     * @var list<string>
     */
    public const PLANS = ['free', 'monitor', 'admin'];

    /**
     * 無料で使える、1つのイベントの参加者の人数（参加者カードも含む）。
     */
    public const FREE_PARTICIPANT_LIMIT = 40;

    /**
     * 採用されたモニターが使える、1つのイベントの参加者の人数。
     */
    public const MONITOR_PARTICIPANT_LIMIT = 100;

    /**
     * 無料で使える、1つのイベントのお宝QR（景品）の数。当たりもハズレも数える。
     */
    public const FREE_PRIZE_LIMIT = 100;

    /**
     * 採用されたモニターが使える、1つのイベントのお宝QR（景品）の数。
     */
    public const MONITOR_PRIZE_LIMIT = 300;

    /**
     * 1つのアカウントで持てるイベントの数（ゴミ箱の中は数えない）。サーバーに負担をかけないための上限で、ふだんの使い方では届かない。
     */
    public const EVENT_LIMIT = 50;

    /**
     * 1つのイベントに参加できる人数（参加者カードも含む）。null は上限なし（運営者）。
     */
    public function participantLimit(): ?int
    {
        return match ($this->plan) {
            'admin' => null,
            'monitor' => self::MONITOR_PARTICIPANT_LIMIT,
            default => self::FREE_PARTICIPANT_LIMIT,
        };
    }

    /**
     * 1つのイベントに登録できるお宝QR（景品）の数。当たりもハズレも数える。null は上限なし（運営者）。
     */
    public function prizeLimit(): ?int
    {
        return match ($this->plan) {
            'admin' => null,
            'monitor' => self::MONITOR_PRIZE_LIMIT,
            default => self::FREE_PRIZE_LIMIT,
        };
    }

    /**
     * 新しくイベントを作れるかどうか（運営者は上限なし）。
     */
    public function canCreateEvent(): bool
    {
        return $this->plan === 'admin' || $this->events()->count() < self::EVENT_LIMIT;
    }

    public function isMonitorOrAdmin(): bool
    {
        return in_array($this->plan, ['monitor', 'admin'], true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
