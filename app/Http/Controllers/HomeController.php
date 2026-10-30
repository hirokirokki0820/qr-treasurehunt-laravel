<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RememberGuestVisitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * トップページ。主催者用のページは /host 以下に分かれていて、ログイン状態もここからは見えない。
 * ゲストには主催者向けの案内を見せず、参加中ならマイページへ、参加前やログアウト後ならゲスト用ページ（参加用QRの読み取り）へ送る。
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (Auth::guard('participant')->check()) {
            return redirect()->route('play.index');
        }

        if (RememberGuestVisitor::isGuest($request)) {
            return redirect()->route('guest.home');
        }

        return view('welcome');
    }
}
