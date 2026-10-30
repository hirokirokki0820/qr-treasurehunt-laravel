<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * イベントとその景品は、作成した主催者だけが操作できる。
 */
class EventPolicy
{
    public function manage(User $user, Event $event): bool
    {
        return $event->user_id === $user->id;
    }
}
