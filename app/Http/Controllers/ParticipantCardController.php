<?php

namespace App\Http\Controllers;

use App\Enums\ClaimResult;
use App\Enums\TimerState;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * 参加者カード（スマホがない子用）。
 * 主催者がカードを作って配り、受付で「参加者カード → お宝QR」の順に主催者のスマホで読み取る。
 * 当たりならその場で景品を渡すので、獲得と同時に引き換え済みにする。
 * カードのQRは主催者のページを指すので、子どもが自分のスマホで読んでも、主催者のログイン画面になるだけ。
 */
class ParticipantCardController extends Controller
{
    /**
     * 1回に作れる枚数。
     */
    public const MAX_PER_REQUEST = 100;

    public function index(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('cards.index', [
            'event' => $event,
            'cards' => $event->participants()->whereNotNull('card_token')->withCount('prizes')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $data = $request->validate([
            'names' => ['nullable', 'string', 'max:5000'],
            'count' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_REQUEST],
        ], attributes: ['names' => '名前', 'count' => '枚数']);

        $names = collect(preg_split('/\R/u', (string) ($data['names'] ?? '')))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->map(fn (string $name) => mb_substr($name, 0, 30))
            ->values();

        if ($names->isEmpty()) {
            $count = (int) ($data['count'] ?? 0);
            if ($count === 0) {
                return back()->withErrors(['names' => '名前を1行に1人ずつ入れるか、枚数を入れてください。']);
            }
            $start = $event->participants()->whereNotNull('card_token')->count();
            $names = collect(range($start + 1, $start + $count))->map(fn (int $n) => 'カード'.$n);
        }

        if ($names->count() > self::MAX_PER_REQUEST) {
            return back()->withInput()->withErrors(['names' => '1回に作れるのは'.self::MAX_PER_REQUEST.'枚までです。']);
        }

        $created = $event->createCards($names->all());

        if ($created < $names->count()) {
            return back()->with('status', "参加者カードを{$created}枚作りました。お試しモード中は".Event::TRIAL_PARTICIPANT_LIMIT.'人までなので、残りは作りませんでした。');
        }

        return back()->with('status', "参加者カードを{$created}枚作りました。印刷して配ってください。");
    }

    public function print(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('cards.print', [
            'event' => $event,
            'cards' => $event->participants()->whereNotNull('card_token')->orderBy('id')->get(),
        ]);
    }

    /**
     * 参加者カードを読み取るカメラ画面。
     */
    public function scan(): View
    {
        return view('cards.scan');
    }

    /**
     * カードを読み取った先。その子の番として、お宝QRを読み取るカメラを開く。
     */
    public function play(Participant $card): View
    {
        Gate::authorize('manage', $card->event);
        $card->loadMissing('event');

        return view('cards.play', [
            'card' => $card,
            'event' => $card->event,
            'prizes' => $card->prizes()->orderBy('acquired_at')->get(),
            'timer' => $card->event->timerState(),
        ]);
    }

    /**
     * カードの子の番で、お宝QRを読み取った先。宝箱を見せ、「開ける！」（POST）で確定する。
     */
    public function treasure(Request $request, Participant $card, Prize $prize): View|RedirectResponse
    {
        Gate::authorize('manage', $card->event);

        if ($prize->event_id !== $card->event_id) {
            return redirect()->route('cards.play', $card->card_token)
                ->withErrors(['prize' => 'このお宝QRは、別のイベントのものです。']);
        }

        $timer = $card->event->timerState();
        $result = ClaimResult::tryFrom((string) $request->session()->get('claim_result'));

        if ($result === null) {
            $result = match (true) {
                $prize->participant_id === $card->id => ClaimResult::AlreadyMine,
                $timer === TimerState::Waiting => ClaimResult::NotStarted,
                $timer === TimerState::Ended => ClaimResult::TimeUp,
                $card->hasReachedPrizeLimit() => ClaimResult::LimitReached,
                ! $prize->is_miss && $prize->participant_id !== null => ClaimResult::TakenByOther,
                default => null,
            };
        }

        return view('cards.treasure', [
            'card' => $card,
            'prize' => $prize,
            'result' => $result,
            'limitReached' => $card->hasReachedPrizeLimit(),
        ]);
    }

    public function claim(Participant $card, Prize $prize): RedirectResponse
    {
        Gate::authorize('manage', $card->event);
        abort_if($prize->event_id !== $card->event_id, 404);

        $result = $prize->claimFor($card);

        // カードの子には、その場で景品を渡すので、獲得と同時に引き換え済みにする
        if ($result === ClaimResult::Won) {
            $prize->forceFill(['redeemed_at' => now()])->save();
        }

        return redirect()->route('cards.treasure', [$card->card_token, $prize->token])->with('claim_result', $result->value);
    }
}
