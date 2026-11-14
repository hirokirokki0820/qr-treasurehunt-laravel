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

        return view('join.create', ['event' => $event, 'trialFull' => $event->isTrialFull()]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        if ($this->currentParticipant()?->event_id === $event->id) {
            return redirect()->route('play.index');
        }

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:30'],
        ], attributes: ['nickname' => 'ニックネーム']);

        // お試しモードの人数は、同時に参加しても超えないよう、イベントの行をロックして数える
        $participant = DB::transaction(function () use ($event, $validated): ?Participant {
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            return $locked->isTrialFull() ? null : $locked->participants()->create($validated);
        });

        if ($participant === null) {
            return back()->withInput()->withErrors(['nickname' => 'このイベントは今お試しモードで、参加できるのは'.Event::TRIAL_PARTICIPANT_LIMIT.'人までです。本番が始まるまでお待ちください。']);
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
