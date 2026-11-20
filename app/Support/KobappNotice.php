<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * kobapp.jp の管理画面から出した、障害・メンテナンスのお知らせ（主催者の画面に出す）。
 * 1分ごとに kobapp.jp に取りに行き、取れないとき（kobapp.jp が止まっているときなど）は何も出さない。
 */
class KobappNotice
{
    /** @var list<string> */
    public const LEVELS = ['info', 'warning'];

    /**
     * @return array{message: string, level: string}|null
     */
    public static function current(): ?array
    {
        return Cache::remember('kobapp-site-notice', 60, function (): ?array {
            try {
                $response = Http::timeout(2)->acceptJson()->get(rtrim((string) config('services.kobapp.url'), '/').'/api/site-notice');
            } catch (Throwable) {
                return null;
            }

            $notice = $response->successful() ? $response->json('notice') : null;
            if (! is_array($notice) || ! is_string($notice['message'] ?? null) || $notice['message'] === '' || ! in_array($notice['level'] ?? null, self::LEVELS, true)) {
                return null;
            }

            return ['message' => mb_substr($notice['message'], 0, 200), 'level' => $notice['level']];
        });
    }
}
