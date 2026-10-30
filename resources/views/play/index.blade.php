<x-layouts.app title="マイページ">
    <p class="text-sm font-bold text-ink-soft">{{ $event->name }}</p>
    <h1 class="text-2xl font-black">{{ $participant->nickname }} さんのお宝</h1>
    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-ink px-3 py-1 text-xs font-bold text-white">あなたの参加者ID<span class="font-black tracking-wide text-gold tabular-nums">{{ $participant->displayId() }}</span></p>

    @if ($event->description)
        <p class="mt-2 whitespace-pre-line text-ink-soft">{{ $event->description }}</p>
    @endif

    @if ($remainingAllowance === 0)
        <x-limit-reached :event="$event" class="mt-6" />
    @else
        @if ($remainingAllowance !== null)
            <p class="mt-4 inline-block rounded-full bg-gold/25 px-3 py-1 text-sm font-bold">
                お宝は{{ $event->prizeLimitLabel() }} ・ あと{{ $remainingAllowance }}個
            </p>
        @endif
        <a href="{{ route('play.scan') }}" class="btn-primary mt-4 w-full py-4 text-xl"><x-icon name="camera" class="h-7 w-7 shrink-0" />QRコードを読み取る</a>
        <p class="mt-2 text-center text-xs text-ink-soft">スマホ標準のカメラアプリで読み取ってもOK</p>
    @endif

    <section class="card mt-8">
        <div class="flex items-baseline justify-between">
            <h2 class="text-lg font-black">獲得した景品</h2>
            <span class="text-2xl font-black">{{ $prizes->count() }}<span class="text-sm"> 個</span></span>
        </div>
        @if ($countedMissCount > 0)
            <p class="mt-1 text-right text-xs text-ink-soft">ほかに、開けたハズレ {{ $countedMissCount }}個 を獲得上限に数えています</p>
        @endif

        @if ($prizes->isEmpty())
            <p class="mt-4 py-6 text-center text-ink-soft">まだお宝はありません。会場のQRコードを探そう！</p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($prizes as $prize)
                    @if ($prize->isRedeemed())
                        <li class="flex items-center gap-3 rounded-xl border-2 border-ink/15 bg-ink/5 p-3">
                            <x-chest class="h-10 w-10 shrink-0 opacity-50 grayscale" :open="true" />
                            <div class="min-w-0 flex-1">
                                <p class="font-black text-ink-soft">{{ $prize->name }}</p>
                                <p class="text-xs text-ink-soft">No.{{ $prize->number }}・{{ $prize->acquired_at?->format('H:i') }} 獲得</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-sea px-2.5 py-1 text-xs font-bold whitespace-nowrap text-white">✓ 引き換え済み</span>
                        </li>
                    @else
                        <li class="flex items-center gap-3 rounded-xl border-2 border-gold bg-gold/15 p-3">
                            <x-chest class="h-10 w-10 shrink-0" :open="true" />
                            <div class="min-w-0 flex-1">
                                <p class="font-black">{{ $prize->name }}</p>
                                <p class="text-xs text-ink-soft">No.{{ $prize->number }}・{{ $prize->acquired_at?->format('H:i') }} 獲得</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-gold/40 px-2.5 py-1 text-xs font-bold whitespace-nowrap">未引き換え</span>
                        </li>
                    @endif
                @endforeach
            </ul>

            @php($unredeemedCount = $prizes->reject->isRedeemed()->count())
            @if ($unredeemedCount > 0)
                <div class="mt-5 rounded-2xl border-2 border-dashed border-ink/40 bg-parchment/60 p-4 text-center">
                    <p class="font-black">景品の引き換え用QRコード</p>
                    <p class="mt-1 text-xs text-ink-soft">受付でこのQRコードを主催者に見せて、景品と交換してね（未引き換え {{ $unredeemedCount }}個）</p>
                    <div class="mx-auto mt-3 aspect-square w-full max-w-44 rounded-lg bg-white p-2" data-qr="{{ route('redemption.lookup', $participant->redeem_token) }}"></div>
                    <p class="mt-2 text-xs text-ink-soft">参加者ID <span class="font-black text-ink">{{ $participant->displayId() }}</span></p>
                </div>
            @else
                <p class="mt-4 rounded-xl bg-sea/10 px-3 py-3 text-center text-sm font-bold text-sea">✓ 獲得した景品はすべて引き換え済みです</p>
            @endif
        @endif
    </section>

    <details class="mt-10 text-sm text-ink-soft">
        <summary class="cursor-pointer font-bold">宝探しをやめる</summary>
        <div class="mt-3 card border-ruby shadow-[4px_4px_0_0_var(--color-ruby)]">
            <p>ゲスト参加なので、終了すると<strong class="text-ruby">再ログインはできず、獲得した景品も無効</strong>になります。</p>
            <form method="POST" action="{{ route('play.logout') }}" class="mt-3" data-confirm="本当に宝探しをやめますか？獲得した景品は無効になります。">
                @csrf
                <button type="submit" class="btn-danger">終了する</button>
            </form>
        </div>
    </details>
</x-layouts.app>
