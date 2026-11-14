<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * お試しモード（本番の前に、参加者3人までで流れを試す）の開始と終了。
 */
class EventTrialController extends Controller
{
    public function start(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        if (! $event->canStartTrial()) {
            return back()->withErrors(['trial' => 'お試しモードは、まだ誰も参加していないイベントで始められます。']);
        }

        $event->startTrial();

        return back()->with('status', 'お試しモードを始めました（参加者'.Event::TRIAL_PARTICIPANT_LIMIT.'人まで）。参加用QRから参加して、流れを試してください。');
    }

    public function end(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_unless($event->trial_mode, 409);

        $event->endTrial();

        return back()->with('status', 'お試しを終えました。お試しの参加者と獲得の記録、タイマーを消したので、このまま本番を始められます。');
    }
}
