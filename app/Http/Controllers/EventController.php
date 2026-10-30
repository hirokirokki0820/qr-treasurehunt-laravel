<?php

namespace App\Http\Controllers;

use App\Enums\JoinQrPrintSize;
use App\Enums\QrPrintSize;
use App\Http\Requests\EventRequest;
use App\Models\Event;
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

        return view('events.index', ['events' => $events]);
    }

    /**
     * イベント作成フォーム。?copy_from=ID を付けると、過去のイベントの内容を初期値にする。
     */
    public function create(Request $request): View
    {
        $pastEvents = $request->user()->events()->withCount('prizes')->latest()->get();

        $source = $request->filled('copy_from')
            ? $pastEvents->firstWhere('id', (int) $request->query('copy_from'))
            : null;

        $event = new Event($source?->only(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit']) ?? []);

        return view('events.create', [
            'event' => $event,
            'pastEvents' => $pastEvents,
            'source' => $source,
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $source = $request->filled('copy_from')
            ? $request->user()->events()->findOrFail($request->integer('copy_from'))
            : null;

        $event = DB::transaction(function () use ($request, $source): Event {
            $event = $request->user()->events()->create($request->safe()->only(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit']));

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
            'stock' => ['nullable', 'in:available'],
        ]);

        $prizes = $event->prizes()
            ->with('participant')
            ->search($filters)
            ->orderBy('number')
            ->get();

        $event->loadCount([
            'prizes as winning_prizes_count' => fn ($query) => $query->where('is_miss', false),
            'prizes as remaining_prizes_count' => fn ($query) => $query->where('is_miss', false)->whereNull('participant_id'),
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

        $event->update($request->safe()->only(['name', 'description', 'max_prizes_per_participant', 'count_misses_toward_limit']));

        return redirect()->route('events.show', $event)->with('status', 'イベントを更新しました。');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $event->delete();

        return redirect()->route('events.index')->with('status', 'イベントを削除しました。');
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

        return redirect()->route('events.index')->with('status', "選択したイベントを{$deletedCount}件削除しました。");
    }

    /**
     * イベントを終了し、参加者を強制ログアウトさせて景品の獲得状況をリセットする。
     */
    public function end(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $loggedOutCount = $event->end();

        return redirect()->route('events.show', $event)
            ->with('status', "イベントを終了しました。参加者{$loggedOutCount}人をログアウトさせ、景品の獲得状況をリセットしました。");
    }

    /**
     * 景品の獲得状況をリセットし、在庫をすべて「○」に戻す。
     */
    public function resetAcquisitions(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $resetCount = $event->resetAcquisitions();

        return redirect()->route('events.show', $event)->with('status', "景品の獲得状況をリセットしました（{$resetCount}件）。");
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
}
