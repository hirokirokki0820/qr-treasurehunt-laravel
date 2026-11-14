@php
    use App\Enums\TimerState;
    $limitReached = $card->hasReachedPrizeLimit();
@endphp

<x-layouts.app :title="$card->nickname.'の番'">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '参加者カード', 'url' => route('cards.index', $event)], ['label' => $card->nickname.'の番']]" />

    <div class="card mt-3">
        <p class="text-sm font-bold text-ink-soft">参加者カード ・ {{ $card->displayId() }}</p>
        <h1 class="text-2xl font-black">{{ $card->nickname }} の番</h1>
        <p class="mt-1 text-sm text-ink-soft">
            獲得 {{ $prizes->count() }}個
            @if ($event->hasPrizeLimit())
                ・ {{ $event->prizeLimitLabel() }}（あと{{ $card->remainingPrizeAllowance() }}個）
            @endif
        </p>
    </div>

    <div class="mt-4">
        <x-errors />
    </div>

    @if (! $timer->acceptsTreasures())
        <p class="card mt-4 text-center font-bold">{{ $timer === TimerState::Waiting ? 'まだ宝探しをスタートしていません。イベントのページで「宝探しスタート」を押してください。' : '宝探しは終了しました。' }}</p>
    @elseif ($limitReached)
        <p class="card mt-4 text-center font-bold">この子は獲得上限（{{ $event->prizeLimitLabel() }}）に達しています。</p>
    @else
        <h2 class="mt-6 text-lg font-black">持ってきたお宝QRを読み取る</h2>
        <x-qr-scanner accept="treasure" :autostart="true" :rewrite-to="parse_url(route('cards.play', $card->card_token), PHP_URL_PATH).'/treasure/'" class="mt-3" />
    @endif

    @if ($prizes->isNotEmpty())
        <section class="card mt-6">
            <h2 class="font-black">この子が獲得した景品</h2>
            <ul class="mt-2 divide-y divide-ink/15 text-sm">
                @foreach ($prizes as $prize)
                    <li class="flex justify-between gap-3 py-1.5">
                        <span><span class="font-black">No.{{ $prize->number }}</span> {{ $prize->name }}</span>
                        <span class="text-xs text-ink-soft">{{ $prize->isRedeemed() ? '渡し済み' : '未' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <a href="{{ route('cards.scan') }}" class="btn-secondary mt-6 w-full gap-2"><x-icon name="camera" class="h-5 w-5 shrink-0" />次の子のカードを読み取る</a>
</x-layouts.app>
