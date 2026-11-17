<?php

namespace App\Http\Controllers;

use App\Enums\JoinQrPrintSize;
use App\Enums\QrPrintSize;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = $request->user()->events()
            ->withCount([
                'prizes as winning_prizes_count' => fn ($query) => $query->where('is_miss', false),
                'prizes as remaining_prizes_count' => fn ($query) => $query->where('is_miss', false)->whereNull('participant_id'),
                'participants',
            ])
            ->latest()
            ->get();

        // 削除して30日たったものは、ここで本当に消す（決まった時刻に動かす仕組みがなくても消えるように）
        Event::purgeExpiredTrash();

        // 並べ方：4件までは全部。5〜10件は新しい4件を出して残りは折りたたむ。11件以上は5件ずつ「さらに表示」で足す
        $total = $events->count();
        $shown = match (true) {
            $total <= 10 => $total,
            default => min($total, max(5, (int) ceil(max(5, (int) $request->query('show', 5)) / 5) * 5)),
        };

        return view('events.index', [
            'events' => $events->take($shown)->values(),
            'total' => $total,
            'foldAfter' => $total >= 5 && $total <= 10 ? 4 : null,
            'trashedCount' => $request->user()->events()->onlyTrashed()->count(),
        ]);
    }

    /**
     * 最近削除したイベント（削除してから30日間は戻せる）。
     */
    public function trash(Request $request): View
    {
        Event::purgeExpiredTrash();

        return view('events.trash', [
            'trashed' => $request->user()->events()->onlyTrashed()->latest('deleted_at')->get(),
        ]);
    }

    /**
     * イベント作成フォーム。?copy_from=ID を付けると、過去のイベントの内容を初期値にする。
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->canCreateEvent()) {
            return $this->eventLimitReached();
        }

        $pastEvents = $request->user()->events()->withCount('prizes')->latest()->get();

        $source = $request->filled('copy_from')
            ? $pastEvents->firstWhere('id', (int) $request->query('copy_from'))
            : null;

        $event = new Event($source?->only(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit', 'time_limit_minutes']) ?? []);

        return view('events.create', [
            'event' => $event,
            'pastEvents' => $pastEvents,
            'source' => $source,
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        if (! $request->user()->canCreateEvent()) {
            return $this->eventLimitReached();
        }

        $source = $request->filled('copy_from')
            ? $request->user()->events()->findOrFail($request->integer('copy_from'))
            : null;

        $event = DB::transaction(function () use ($request, $source): Event {
            $event = $request->user()->events()->create([...$request->safe()->only(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit', 'time_limit_minutes']), ...$request->schedule()]);

            if ($source !== null && $request->boolean('copy_prizes')) {
                $event->copyPrizesFrom($source);
            }

            return $event;
        });

        $message = $event->prizes()->exists()
            ? "イベントを作成しました。「{$source?->name}」の景品を引き継いでいます。"
            : 'イベントを作成しました。次は景品を登録しましょう。';

        return redirect()->route('events.show', $event)->with('status', $message);
    }

    /**
     * イベント詳細 兼 景品一覧（検索・在庫確認）。
     */
    public function show(Request $request, Event $event): View
    {
        Gate::authorize('manage', $event);

        $filters = $request->validate([
            'stock' => ['nullable', 'in:available,unredeemed'],
        ]);

        $prizes = $event->prizes()
            ->with('participant')
            ->search($filters)
            ->orderBy('number')
            ->get();

        $event->loadCount([
            'prizes as winning_prizes_count' => fn ($query) => $query->where('is_miss', false),
            'prizes as remaining_prizes_count' => fn ($query) => $query->where('is_miss', false)->whereNull('participant_id'),
            'prizes',
            'prizes as unredeemed_prizes_count' => fn ($query) => $query->where('is_miss', false)->whereNotNull('participant_id')->whereNull('redeemed_at'),
            'participants',
        ]);

        return view('events.show', [
            'event' => $event,
            'prizes' => $prizes,
            'filters' => $filters,
            'duplicatedNicknames' => $event->duplicatedNicknames(),
        ]);
    }

    /**
     * イベントの参加者（ニックネーム）一覧。
     */
    public function participants(Event $event): View
    {
        Gate::authorize('manage', $event);

        $participants = $event->participants()
            ->withCount([
                'prizes',
                'prizes as unredeemed_prizes_count' => fn ($query) => $query->whereNull('redeemed_at'),
            ])
            ->oldest()
            ->oldest('id')
            ->get();

        return view('events.participants', [
            'event' => $event,
            'participants' => $participants,
            'duplicatedNicknames' => $event->duplicatedNicknames(),
        ]);
    }

    public function edit(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('events.edit', ['event' => $event]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $schedule = $request->schedule();

        // 本番が始まったあとは、予定を消せない（変えることはできる）。消すと、新しい参加を受け付けなくなるため
        if ($schedule['scheduled_start_at'] === null && $event->hasSchedule() && $event->requires_schedule && in_array($event->phase(), ['live', 'closed'], true)) {
            return back()->withInput()->withErrors(['scheduled_date' => '開催中のイベントは、開催予定を空欄にできません。日時を変えるときは、新しい日時を入れてください。']);
        }

        $event->update([...$request->safe()->only(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit', 'time_limit_minutes']), ...$schedule]);

        return redirect()->route('events.show', $event)->with('status', 'イベントを更新しました。');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $event->delete();

        return redirect()->route('events.index')->with('status', 'イベントを削除しました。'.Event::TRASH_DAYS.'日以内なら、「最近削除したイベント」から戻せます。');
    }

    /**
     * イベント一覧でチェックしたイベントをまとめて削除する。自分のイベント以外のIDは無視する。
     */
    public function destroyMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_ids' => ['required', 'array'],
            'event_ids.*' => ['integer'],
        ], [
            'event_ids.required' => '削除するイベントを選択してください。',
        ]);

        $deletedCount = $request->user()->events()->whereIn('id', $validated['event_ids'])->delete();

        return redirect()->route('events.index')->with('status', "選択したイベントを{$deletedCount}件削除しました。".Event::TRASH_DAYS.'日以内なら、「最近削除したイベント」から戻せます。');
    }

    /**
     * 削除したイベントを戻す（削除してから30日以内）。景品・参加者・獲得の記録も、消す前のまま戻る。
     */
    public function restore(Request $request, int $event): RedirectResponse
    {
        $trashed = $request->user()->events()->onlyTrashed()
            ->where('deleted_at', '>', now()->subDays(Event::TRASH_DAYS))
            ->findOrFail($event);

        $trashed->restore();

        return redirect()->route('events.index')->with('status', "「{$trashed->name}」を戻しました。");
    }

    /**
     * イベントを終了し、参加者を強制ログアウトさせて景品の獲得状況をリセットする。
     */
    public function end(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $loggedOutCount = $event->end();

        return redirect()->route('events.show', $event)
            ->with('status', "イベントを片付けました。参加者{$loggedOutCount}人の記録を消し、景品をすべて未獲得に戻しました。景品とQRコードは、次の回にそのまま使えます。");
    }

    /**
     * 景品の獲得状況をリセットし、在庫をすべて「○」に戻す。
     */
    public function resetAcquisitions(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $resetCount = $event->resetAcquisitions();

        return redirect()->route('events.show', $event)->with('status', "景品の獲得記録をリセットしました（{$resetCount}件を未獲得に戻しました）。参加者とタイマーはそのままです。");
    }

    /**
     * 宝のQRコードを印刷用に並べて表示する。
     */
    public function qrCodes(Request $request, Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('events.qrcodes', [
            'event' => $event,
            'prizes' => $event->prizes()->orderBy('number')->get(),
            'size' => QrPrintSize::tryFrom((string) $request->query('size')) ?? QrPrintSize::Large,
            'sizes' => QrPrintSize::cases(),
        ]);
    }

    /**
     * 緊急時（全員つながらないとき）に紙で続けるための景品リストを、印刷用に表示する。
     * 開いただけでは「印刷済み」にしない。印刷できたら、主催者が「印刷できた」を押す（markPrizeListPrinted）。
     */
    public function prizeList(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('events.prize-list', [
            'event' => $event,
            'prizes' => $event->prizes()->with('participant')->orderBy('number')->get(),
        ]);
    }

    /**
     * 「控えを保存」を押したときに、その時点の獲得状況を返す（つながらないときは、ページを開いたときの状態で作る）。
     */
    public function snapshot(Event $event): JsonResponse
    {
        Gate::authorize('manage', $event);

        return response()->json($event->snapshotData())->header('Cache-Control', 'no-store');
    }

    /**
     * 景品リストを印刷できたことを記録し、イベントのページの「印刷しておきましょう」のお知らせを消す。
     */
    public function markPrizeListPrinted(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $event->markPrizeListPrinted();

        return redirect()->route('events.show', $event)->with('status', '景品リストを印刷済みにしました。受付に置いておいてください。');
    }

    /**
     * 参加者がゲストログインするためのQRコードを表示する。
     */
    public function joinQrCode(Request $request, Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('events.join-qrcode', [
            'event' => $event,
            'size' => JoinQrPrintSize::tryFrom((string) $request->query('size')) ?? JoinQrPrintSize::Large,
            'sizes' => JoinQrPrintSize::cases(),
        ]);
    }

    /**
     * イベントの数の上限に達したときに、イベント一覧へ戻して知らせる。
     */
    private function eventLimitReached(): RedirectResponse
    {
        return redirect()->route('events.index')->withErrors([
            'event' => 'イベントは1つのアカウントで'.User::EVENT_LIMIT.'件まで作れます。使い終わったイベントを削除してから作ってください。',
        ]);
    }
}
