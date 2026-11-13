<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\KobappAuthException;
use App\Http\Controllers\Controller;
use App\Services\KobappAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 主催者は「kobapp でログイン」でログインする（kobapp.jp とのやりとりは KobappAuth にまとめてある）。
 */
class KobappLoginController extends Controller
{
    public function __construct(private KobappAuth $kobapp) {}

    /**
     * kobapp.jp のログイン画面へ送る。
     */
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->away($this->kobapp->authorizationUrl($request));
    }

    /**
     * kobapp.jp から戻ってきたとき。
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $user = $this->kobapp->userFromCallback($request);
        } catch (KobappAuthException $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('events.index'));
    }
}
