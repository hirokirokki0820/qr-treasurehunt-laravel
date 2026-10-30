<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * 受付での景品の引き換え。引き換え済みにできるのは主催者だけ。
 */
class RedemptionController extends Controller
{
    /**
     * 参加者の「引き換え用QRコード」を読み取るカメラ画面。
     */
    public function scan(): View
    {
        return view('redemption.scan');
    }

    /**
     * 引き換え用QRコードの読み取り先。その参加者の獲得景品ページを開く。
     */
    public function lookup(Participant $participant): RedirectResponse
    {
        Gate::authorize('manage', $participant->event);

        return redirect()->route('events.participants.show', [$participant->event, $participant]);
    }

    /**
     * 参加者が獲得した景品を、まとめて引き換え済みにする。
     */
    public function redeemAll(Event $event, Participant $participant): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $count = $participant->redeemAllPrizes();

        return redirect()->route('events.participants.show', [$event, $participant])
            ->with('status', "{$participant->nickname} さんの景品 {$count}個 を引き換え済みにしました。");
    }

    /**
     * 景品を1つ引き換え済みにする。
     */
    public function redeem(Event $event, Prize $prize): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_if($prize->participant_id === null, 404);

        if (! $prize->isRedeemed()) {
            $prize->forceFill(['redeemed_at' => now()])->save();
        }

        return redirect()->route('events.participants.show', [$event, $prize->participant_id])
            ->with('status', "No.{$prize->number}「{$prize->name}」を引き換え済みにしました。");
    }

    /**
     * 押し間違えたときのために、引き換えを取り消す。
     */
    public function cancel(Event $event, Prize $prize): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_if($prize->participant_id === null, 404);

        $prize->forceFill(['redeemed_at' => null])->save();

        return redirect()->route('events.participants.show', [$event, $prize->participant_id])
            ->with('status', "No.{$prize->number}「{$prize->name}」の引き換えを取り消しました。");
    }
}
