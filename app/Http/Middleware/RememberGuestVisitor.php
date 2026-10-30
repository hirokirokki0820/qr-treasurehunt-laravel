<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 参加者向けのページ（参加受付・宝のQR・マイページ・ゲスト用ページ）を開いた人を「ゲスト」として覚えておく。
 * こうしておくと、その人がトップページを開いても主催者向けの画面ではなくゲスト用ページを表示できる。
 */
class RememberGuestVisitor
{
    public const SESSION_KEY = 'guest_visitor';

    /**
     * 参加者向けのページを開いたことがあるゲストかどうか。
     */
    public static function isGuest(Request $request): bool
    {
        return (bool) $request->session()->get(self::SESSION_KEY);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->session()->put(self::SESSION_KEY, true);

        return $next($request);
    }
}
