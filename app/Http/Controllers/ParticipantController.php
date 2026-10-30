<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * 主催者が見る、参加者ごとの獲得状況。
 */
class ParticipantController extends Controller
{
    public function show(Event $event, Participant $participant): View
    {
        Gate::authorize('manage', $event);

        $prizes = $participant->prizes()
            ->orderBy('acquired_at')
            ->orderBy('number')
            ->get();

        $misses = $participant->foundMisses()
            ->orderByPivot('created_at')
            ->get();

        return view('participants.show', [
            'event' => $event,
            'participant' => $participant,
            'prizes' => $prizes,
            'misses' => $misses,
        ]);
    }
}
