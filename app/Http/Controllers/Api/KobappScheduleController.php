<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * kobapp.jp の管理画面（開催カレンダー）に、イベントの開催予定を渡す。サーバーどうしの問い合わせだけを受ける。
 *
 * 確かめ方：kobapp が「時刻\n開始日\n終了日」を「kobapp でログイン」の合言葉（client_secret）で HMAC-SHA256 にかけて送る。
 * 合言葉はこのサーバーと kobapp のサーバーの中にしかないので、ほかの人は問い合わせられない。5分より古い問い合わせは受けない。
 * 渡すのは、イベント名・日時・状態・人数と、主催者の kobapp のID（sub）だけ。メールアドレスなどは渡さない（kobapp が自分で持っている）。
 */
class KobappScheduleController extends Controller
{
    private const MAX_AGE_SECONDS = 300;

    private const MAX_RANGE_DAYS = 62;

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.kobapp.client_secret');
        $timestamp = (string) $request->header('X-Kobapp-Timestamp', '');
        $signature = (string) $request->header('X-Kobapp-Signature', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        if ($secret === '' || ! ctype_digit($timestamp) || abs(now()->getTimestamp() - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return response()->json(['error' => 'unauthorized'], 401);
        }
        if (! hash_equals(hash_hmac('sha256', $timestamp."\n".$from."\n".$to, $secret), $signature)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        try {
            $start = Carbon::createFromFormat('!Y-m-d', $from, config('app.timezone'));
            $end = Carbon::createFromFormat('!Y-m-d', $to, config('app.timezone'))->endOfDay();
        } catch (Throwable) {
            return response()->json(['error' => 'bad_range'], 422);
        }
        if ($end->lessThan($start) || $start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return response()->json(['error' => 'bad_range'], 422);
        }

        $events = Event::query()
            ->with('user:id,kobapp_user_id,plan')
            ->withCount(['participants', 'prizes'])
            ->where(fn ($query) => $query
                ->whereBetween('scheduled_start_at', [$start, $end])
                ->orWhere(fn ($q) => $q->whereNull('scheduled_start_at')->whereBetween('started_at', [$start, $end])))
            ->orderByRaw('coalesce(scheduled_start_at, started_at)')
            ->limit(1000)
            ->get();

        $unscheduled = Event::query()
            ->with('user:id,kobapp_user_id,plan')
            ->where('requires_schedule', true)
            ->whereNull('scheduled_start_at')
            ->whereNull('started_at')
            ->latest('id')
            ->limit(200)
            ->get();

        return response()->json([
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'scheduled_start' => $event->scheduled_start_at?->toIso8601String(),
                'scheduled_minutes' => $event->scheduled_minutes,
                'started_at' => $event->started_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'phase' => $event->phase(),
                'participants' => $event->participants_count,
                'prizes' => $event->prizes_count,
                'owner_sub' => $event->user?->kobapp_user_id,
                'owner_plan' => $event->user?->plan,
            ])->values(),
            'unscheduled' => $unscheduled->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'created_at' => $event->created_at?->toIso8601String(),
                'owner_sub' => $event->user?->kobapp_user_id,
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }
}
