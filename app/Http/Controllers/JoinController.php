<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 参加者のゲストログイン。主催者が配る参加用QRコードからアクセスする。
 */
class JoinController extends Controller
{
    public function create(Event $event): View|RedirectResponse
    {
        if ($this->currentParticipant()?->event_id === $event->id) {
            return redirect()->route('play.index');
        }

        return view('join.create', ['event' => $event, 'full' => $event->isFull(), 'notOpen' => $event->needsScheduleToStart()]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        if ($this->currentParticipant()?->event_id === $event->id) {
            return redirect()->route('play.index');
        }

        // 開催予定が入るまでは、本番の参加を受け付けない（お試しモードは受け付ける）
        if ($event->needsScheduleToStart()) {
            return back()->withInput()->withErrors(['nickname' => 'このイベントは、まだ参加の受付を始めていません。主催者の案内をお待ちください。']);
        }

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:30'],
        ], attributes: ['nickname' => 'ニックネーム']);

        // 参加できる人数は、同時に参加しても超えないよう、イベントの行をロックして数える
        $participant = DB::transaction(function () use ($event, $validated): ?Participant {
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            return $locked->isFull() ? null : $locked->participants()->create($validated);
        });

        if ($participant === null) {
            return back()->withInput()->withErrors(['nickname' => $event->trial_mode
                ? 'このイベントは今お試しモードで、参加できるのは'.Event::TRIAL_PARTICIPANT_LIMIT.'人までです。本番が始まるまでお待ちください。'
                : 'このイベントは、参加できる人数（'.$event->participantLimit().'人）に達しています。主催者にお知らせください。']);
        }

        Auth::guard('participant')->login($participant, remember: true);
        $request->session()->regenerate();

        return redirect()->route('play.index')->with('status', "{$participant->nickname} さん、宝探しスタート！");
    }

    private function currentParticipant(): ?Participant
    {
        /** @var Participant|null */
        return Auth::guard('participant')->user();
    }
}
