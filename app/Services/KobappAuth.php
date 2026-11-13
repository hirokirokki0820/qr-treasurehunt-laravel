<?php

namespace App\Services;

use App\Exceptions\KobappAuthException;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 「kobapp でログイン」で kobapp.jp とやりとりする部分をまとめたもの（ほかの場所からは直接通信しない）。
 *
 * 1. authorizationUrl()：state と PKCE の値を作ってセッションに置き、kobapp.jp の入口の URL を返す
 * 2. 利用者が kobapp.jp でログインすると、確認コードをつけて callback に戻ってくる
 * 3. userFromCallback()：state を確かめ、サーバーから kobapp.jp に直接問い合わせて主催者を特定する
 */
class KobappAuth
{
    private const SESSION_KEY = 'kobapp_auth';

    /** ログインを始めてから戻ってくるまでの制限時間（秒） */
    private const MAX_AGE = 600;

    public function authorizationUrl(Request $request): string
    {
        $state = Str::random(40);
        $verifier = Str::random(64);

        $request->session()->put(self::SESSION_KEY, [
            'state' => $state,
            'verifier' => $verifier,
            'started_at' => now()->getTimestamp(),
        ]);

        return $this->baseUrl().'/sso/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.kobapp.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'code_challenge' => self::challengeFor($verifier),
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * kobapp.jp から戻ってきたときの処理。成功すれば主催者を返す（初めての人は作る）。
     *
     * @throws KobappAuthException
     */
    public function userFromCallback(Request $request): User
    {
        // 使えるのは1回だけ。取り出したらセッションから消す
        $pending = $request->session()->pull(self::SESSION_KEY);

        if (! is_array($pending) || ! isset($pending['state'], $pending['verifier'], $pending['started_at'])) {
            throw new KobappAuthException('ログインの手続きが見つかりませんでした。もう一度「kobapp でログイン」を押してください。');
        }
        if (now()->getTimestamp() - (int) $pending['started_at'] > self::MAX_AGE) {
            throw new KobappAuthException('時間がたったため、ログインを中止しました。もう一度「kobapp でログイン」を押してください。');
        }
        if (! is_string($request->query('state')) || ! hash_equals($pending['state'], $request->query('state'))) {
            throw new KobappAuthException('ログインの手続きを確認できませんでした。もう一度「kobapp でログイン」を押してください。');
        }
        if ($request->query('error') !== null) {
            throw new KobappAuthException($request->query('error') === 'access_denied'
                ? 'ログインをキャンセルしました。'
                : 'kobapp でログインできませんでした。もう一度お試しください。');
        }
        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 255) {
            throw new KobappAuthException('kobapp でログインできませんでした。もう一度お試しください。');
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->post($this->baseUrl().'/sso/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri(),
                    'client_id' => config('services.kobapp.client_id'),
                    'client_secret' => config('services.kobapp.client_secret'),
                    'code_verifier' => $pending['verifier'],
                ]);
        } catch (ConnectionException) {
            throw new KobappAuthException('kobapp につながりませんでした。少し時間をおいてから、もう一度お試しください。');
        }

        $sub = $response->json('sub');
        $email = $response->json('email');
        if (! $response->ok() || ! is_string($sub) || $sub === '' || ! is_string($email) || $response->json('email_verified') !== true) {
            report(new KobappAuthException('kobapp token request failed: HTTP '.$response->status().' '.$response->json('error', '')));

            throw new KobappAuthException('kobapp でログインできませんでした。もう一度お試しください。');
        }

        $user = User::firstOrNew(['kobapp_user_id' => $sub]);
        $user->email = $email;
        $user->save();

        return $user;
    }

    /**
     * PKCE（S256）：code_verifier を SHA-256 にかけて base64url にしたもの。
     */
    public static function challengeFor(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.kobapp.url'), '/');
    }

    private function redirectUri(): string
    {
        return config('services.kobapp.redirect_uri') ?: route('login.kobapp.callback');
    }
}
