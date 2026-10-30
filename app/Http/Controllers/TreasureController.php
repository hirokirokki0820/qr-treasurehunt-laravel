<?php

namespace App\Http\Controllers;

use App\Enums\ClaimResult;
use App\Models\Participant;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 宝のQRコードを読み取った先のページ。
 * GETでは宝箱を表示するだけで、取得は「開ける」ボタン（POST）で確定する。
 */
class TreasureController extends Controller
{
    public function show(Request $request, Prize $prize): View
    {
        /** @var Participant|null $participant */
        $participant = $request->user('participant');

        if ($participant === null) {
            return view('treasure.need-join', ['prize' => $prize]);
        }

        if ($participant->event_id !== $prize->event_id) {
            return $this->otherEventPage($participant, $prize);
        }

        $result = ClaimResult::tryFrom((string) $request->session()->get('claim_result'));

        if ($result === null) {
            $result = match (true) {
                $prize->participant_id === $participant->id => ClaimResult::AlreadyMine,
                $participant->hasReachedPrizeLimit() => ClaimResult::LimitReached,
                ! $prize->is_miss && $prize->participant_id !== null => ClaimResult::TakenByOther,
                default => null,
            };
        }

        return view('treasure.show', [
            'prize' => $prize,
            'result' => $result,
            'limitReached' => $participant->hasReachedPrizeLimit(),
        ]);
    }

    public function claim(Request $request, Prize $prize): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->user('participant');

        if ($participant->event_id !== $prize->event_id) {
            return redirect()->route('treasure.show', $prize);
        }

        $result = $prize->claimFor($participant);

        return redirect()->route('treasure.show', $prize)->with('claim_result', $result->value);
    }

    /**
     * 参加中のイベントとは別のイベントのお宝を読み取ったときの案内ページ。
     */
    private function otherEventPage(Participant $participant, Prize $prize): View
    {
        $participant->loadMissing('event');

        return view('treasure.other-event', [
            'currentEvent' => $participant->event,
            'isSameOrganizer' => $prize->event->user_id === $participant->event->user_id,
            'limitReached' => $participant->hasReachedPrizeLimit(),
        ]);
    }
}
