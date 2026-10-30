<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrizeRequest;
use App\Models\Event;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrizeController extends Controller
{
    public function create(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('prizes.create', ['event' => $event, 'prize' => new Prize]);
    }

    public function store(PrizeRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $prize = $event->prizes()->create($request->validated());

        $message = "景品 No.{$prize->number}「{$prize->name}」を登録しました。";

        if ($request->boolean('continue')) {
            return redirect()->route('events.prizes.create', $event)->with('status', $message);
        }

        return redirect()->route('events.show', $event)->with('status', $message);
    }

    public function show(Request $request, Event $event, Prize $prize): View
    {
        Gate::authorize('manage', $event);

        $prize->load('participant');

        return view('prizes.show', [
            'event' => $event,
            'prize' => $prize,
            'backLink' => $this->backLink($event, $request->query('back')),
        ]);
    }

    public function edit(Request $request, Event $event, Prize $prize): View
    {
        Gate::authorize('manage', $event);

        return view('prizes.edit', [
            'event' => $event,
            'prize' => $prize,
            'back' => $this->backLink($event, $request->query('back'))['param'],
        ]);
    }

    public function update(PrizeRequest $request, Event $event, Prize $prize): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $prize->fill($request->validated());

        if ($prize->is_miss) {
            $prize->participant_id = null;
            $prize->acquired_at = null;
            $prize->redeemed_at = null;
        }

        $prize->save();

        $back = $this->backLink($event, $request->input('back'))['param'];

        return redirect()->route('events.prizes.show', [$event, $prize, 'back' => $back])->with('status', '景品を更新しました。');
    }

    public function destroy(Event $event, Prize $prize): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $prize->delete();

        return redirect()->route('events.show', $event)->with('status', "景品 No.{$prize->number} を削除しました。");
    }

    /**
     * 景品一覧でチェックした景品をまとめて削除する。他のイベントの景品IDは無視する。
     */
    public function destroyMany(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $validated = $request->validate([
            'prize_ids' => ['required', 'array'],
            'prize_ids.*' => ['integer'],
        ], [
            'prize_ids.required' => '削除する景品を選択してください。',
        ]);

        $deletedCount = $event->prizes()->whereIn('id', $validated['prize_ids'])->delete();

        return redirect()->route('events.show', $event)->with('status', "選択した景品を{$deletedCount}件削除しました。");
    }

    /**
     * 景品詳細のパンくずリストと「戻る」の行き先。「残り」で絞り込んだ景品一覧から開いたときは、絞り込んだままの一覧に戻す。
     *
     * @return array{url: string, param: string|null}
     */
    private function backLink(Event $event, mixed $back): array
    {
        if ($back === 'remaining') {
            return [
                'url' => route('events.show', [$event, 'stock' => 'available']).'#prize-list',
                'param' => 'remaining',
            ];
        }

        return [
            'url' => route('events.show', $event).'#prize-list',
            'param' => null,
        ];
    }
}
