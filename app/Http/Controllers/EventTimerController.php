<?php

namespace App\Http\Controllers;

use App\Enums\TimerState;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * 主催者がイベントページで操作する、宝探しタイマー（スタート・延長・締め切り）。
 */
class EventTimerController extends Controller
{
    /**
     * 延長ボタン1回で延ばす分数。
     */
    public const EXTEND_MINUTES = 5;

    public function start(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_unless($event->hasTimeLimit() && $event->timerState() !== TimerState::Running, 409);

        $event->startTimer();

        return back()->with('status', "宝探しをスタートしました（{$event->time_limit_minutes}分）。");
    }

    public function extend(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $event->extendTimer(self::EXTEND_MINUTES);

        return back()->with('status', '終了時刻を'.self::EXTEND_MINUTES.'分延ばしました（'.$event->ends_at?->format('H:i').' まで）。');
    }

    public function stop(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $event->stopTimer();

        return back()->with('status', '宝探しを締め切りました。');
    }
}
