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

        // 別の主催者がログインしたままなら、その人はこのブラウザーからだけログアウトさせる
        // （ほかの端末の「ログインしたまま」は消さない）。セッションも作り直し、前の人の情報を残さない
        $intended = $request->session()->pull('url.intended');
        $guard = Auth::guard('web');
        if ($guard->check() && $guard->id() !== $user->getKey()) {
            $guard->logoutCurrentDevice();
        }
        $request->session()->invalidate();

        // 主催者は、ログアウトするまで30日ログインしたまま（長さは config/auth.php の remember）
        $guard->login($user, remember: true);
        $request->session()->regenerateToken();

        return redirect()->to(is_string($intended) ? $intended : route('events.index'));
    }

    /**
     * kobapp.jp でログアウトしたとき、kobapp.jp からここを通って戻る。このブラウザーの主催者もログアウトする。
     *
     * 他人が勝手にログアウトさせられないよう、kobapp.jp との共通の合言葉で作った署名（5分以内）があるときだけ動く。
     * 戻り先は kobapp.jp のトップに決めてある（どこへでも飛ばせる入口にはしない）。
     */
    public function logout(Request $request): RedirectResponse
    {
        if (self::validLogoutSignature($request->query('ts'), $request->query('sig'))) {
            $guard = Auth::guard('web');
            if ($guard->check()) {
                $guard->logoutCurrentDevice();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->away(rtrim((string) config('services.kobapp.url'), '/').'/');
    }

    /**
     * kobapp.jp 側の署名と同じ作り方（hash_hmac('sha256', "logout\n".時刻, 合言葉)）。
     */
    public static function logoutSignature(string $timestamp): string
    {
        return hash_hmac('sha256', "logout\n".$timestamp, (string) config('services.kobapp.client_secret'));
    }

    private static function validLogoutSignature(mixed $timestamp, mixed $signature): bool
    {
        if (! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($signature) || (string) config('services.kobapp.client_secret') === '') {
            return false;
        }
        if (abs(now()->getTimestamp() - (int) $timestamp) > 300) {
            return false;
        }

        return hash_equals(self::logoutSignature($timestamp), $signature);
    }
}
