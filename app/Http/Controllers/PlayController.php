<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 参加者のマイページとQRスキャナー。
 */
class PlayController extends Controller
{
    public function index(Request $request): View
    {
        $participant = $this->participant($request);

        return view('play.index', [
            'participant' => $participant,
            'event' => $participant->event,
            'prizes' => $participant->prizes()->orderBy('acquired_at')->get(),
            'remainingAllowance' => $participant->remainingPrizeAllowance(),
            'countedMissCount' => $participant->event->count_misses_toward_limit ? $participant->foundMisses()->count() : 0,
        ]);
    }

    /**
     * QRスキャナー。獲得上限に達した参加者や、宝探しの時間外にはスキャナーを出さない。
     */
    public function scan(Request $request): View
    {
        $participant = $this->participant($request);

        return view('play.scan', [
            'event' => $participant->event,
            'limitReached' => $participant->hasReachedPrizeLimit(),
            'timer' => $participant->event->timerState(),
        ]);
    }

    /**
     * 参加者の画面が数秒ごとに問い合わせる、宝探しタイマーの状態。
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json($this->participant($request)->event->timerStatus());
    }

    /**
     * ゲスト参加者は再ログインできないため、ログアウトすると獲得した景品も手放す。
     */
    public function logout(Request $request): RedirectResponse
    {
        $participant = $this->participant($request);

        Auth::guard('participant')->logout();
        $participant->delete();

        $request->session()->regenerateToken();

        return redirect()->route('guest.home')->with('status', '宝探しを終了しました。');
    }

    private function participant(Request $request): Participant
    {
        /** @var Participant */
        return $request->user('participant');
    }
}
