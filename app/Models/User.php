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
     * 正式なサービスを始めるまで、モニターでない人が使える参加者の人数。
     */
    public const FREE_PARTICIPANT_LIMIT = 3;

    /**
     * 採用されたモニターが使える参加者の人数。
     */
    public const MONITOR_PARTICIPANT_LIMIT = 100;

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
