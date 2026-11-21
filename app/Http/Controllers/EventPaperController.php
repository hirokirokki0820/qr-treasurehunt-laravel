<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * 当日つながらなくなったときの「紙に切り替える」。切り替えると、参加者のアプリでは宝箱を開けられなくなり、
 * 「QRカードを取って受付へ」と案内が出る（アプリと紙が混ざって、同じ景品を二重に渡さないように）。
 * 押し間違えたときのために、アプリに戻すこともできる。片付けると元に戻る。
 */
class EventPaperController extends Controller
{
    public function start(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        if (! $event->isPaperMode()) {
            $event->forceFill(['paper_mode_at' => now()])->save();
        }

        return redirect()->route('events.show', $event)->with('status', '紙に切り替えました。参加者のアプリでは宝箱を開けられなくなり、「QRカードを取って受付へ」と案内が出ます。');
    }

    public function end(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $event->forceFill(['paper_mode_at' => null])->save();

        return redirect()->route('events.show', $event)->with('status', 'アプリに戻しました。参加者は、また宝箱を開けられます。');
    }
}
