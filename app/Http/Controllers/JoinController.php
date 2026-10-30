<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('join.create', ['event' => $event]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        if ($this->currentParticipant()?->event_id === $event->id) {
            return redirect()->route('play.index');
        }

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:30'],
        ], attributes: ['nickname' => 'ニックネーム']);

        $participant = $event->participants()->create($validated);

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
