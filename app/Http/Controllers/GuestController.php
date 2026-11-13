<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RememberGuestVisitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 参加していない（またはログアウトした）ゲスト向けのページ。
 * 主催者向けの案内は出さず、参加用QRコードを読み取るスキャナーだけを置く。
 */
class GuestController extends Controller
{
    /**
     * ゲスト用のトップページ。参加前の人や、宝探しをやめた人に表示する。
     */
    public function index(): View
    {
        return view('guest.index', [
            'heading' => "QRコードを読み取って\n参加しよう",
            'lead' => '主催者が用意した「参加用QRコード」をカメラで読み取ると、宝探しに参加できます。',
            'showOrganizerMenu' => true,
        ]);
    }

    /**
     * 参加していない状態で、参加者用のページや宝のQRコードを開いたときのページ。
     */
    public function required(): View
    {
        return view('guest.index', [
            'heading' => 'まずは参加受付をしてね',
            'lead' => '主催者が用意した「参加用QRコード」を読み取って、ニックネームを登録してください。',
        ]);
    }

    /**
     * ゲストの印を外して、主催者専用のトップページへ切り替える。
     * 主催者が参加用QRで試しに参加したあと、トップページがゲスト用のままになるのを戻すため。
     */
    public function switchToOrganizer(Request $request): RedirectResponse
    {
        $request->session()->forget(RememberGuestVisitor::SESSION_KEY);

        return redirect()->route('host');
    }
}
