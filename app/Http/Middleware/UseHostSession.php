<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * 主催者用のページ（/host 以下）だけ、参加者用とは別のセッションクッキーを使う。
 * 同じブラウザで主催者としてログインしたまま参加者としても遊べるよう、ログイン状態を完全に分けるため。
 * セッションを始める前に動かす必要があるので、web ミドルウェアグループの先頭に置いている。
 */
class UseHostSession
{
    public const PATH_PREFIX = 'host';

    private static ?string $baseCookieName = null;

    private static ?string $basePath = null;

    public function __construct(private CookieJar $cookies, private SessionManager $sessions) {}

    /**
     * 主催者用のページ（/host 以下）へのリクエストかどうか。
     */
    public static function isHostRequest(Request $request): bool
    {
        return $request->is(self::PATH_PREFIX, self::PATH_PREFIX.'/*');
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        self::$baseCookieName ??= (string) config('session.cookie');
        self::$basePath ??= (string) config('session.path');

        $isHost = self::isHostRequest($request);
        $path = $isHost ? '/'.self::PATH_PREFIX : self::$basePath;

        $cookieName = $isHost ? self::$baseCookieName.'_host' : self::$baseCookieName;

        config([
            'session.cookie' => $cookieName,
            'session.path' => $path,
        ]);

        // すでに作られたセッションがあっても、このリクエストのクッキー名を使わせる
        $this->sessions->driver()->setName($cookieName);

        // ログイン状態を保持するクッキー（remember_web など）も同じ範囲に限定する
        $this->cookies->setDefaultPathAndDomain(
            $path,
            config('session.domain'),
            (bool) config('session.secure'),
            config('session.same_site'),
        );

        return $next($request);
    }
}
