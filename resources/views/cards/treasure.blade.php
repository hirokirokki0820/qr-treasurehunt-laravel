@php
    use App\Enums\ClaimResult;
@endphp

{{-- 参加者カードの子の番で、主催者のスマホに出す宝箱。子どもと一緒に画面を見ながら開ける --}}
<x-layouts.app :title="$card->nickname.'の宝箱'">
    <div class="card mx-auto max-w-md text-center">
        <p class="text-sm font-bold text-ink-soft">{{ $card->nickname }} の番 ・ お宝 No.{{ $prize->number }}</p>

        @switch($result)
            @case(ClaimResult::Won)
                <x-chest class="mx-auto mt-2 h-36 w-36 motion-safe:animate-bounce" :open="true" />
                <p class="mt-2 text-sm font-black tracking-widest text-gold-deep">おめでとう！</p>
                <h1 class="mt-1 text-3xl font-black">{{ $prize->name }}</h1>
                <p class="mt-1 text-sm text-ink-soft">を手に入れた！</p>
                @if ($prize->description)
                    <p class="mt-4 whitespace-pre-line rounded-xl bg-gold/15 px-3 py-2">{{ $prize->description }}</p>
                @endif
                <p class="mt-4 rounded-xl bg-sea/10 px-3 py-2 text-sm font-bold text-sea">その場で景品を渡してください（引き換え済みにしました）</p>
                @break

            @case(ClaimResult::AlreadyMine)
                <x-chest class="mx-auto mt-2 h-28 w-28" :open="true" />
                <h1 class="mt-3 text-2xl font-black">{{ $prize->name }}</h1>
                <p class="mt-2 text-ink-soft">このお宝は、もうこの子のものです。</p>
                @break

            @case(ClaimResult::TakenByOther)
                <x-chest class="mx-auto mt-2 h-28 w-28 opacity-50 grayscale" :open="true" />
                <h1 class="mt-3 text-2xl font-black">からっぽ…</h1>
                <p class="mt-2 text-ink-soft">このお宝は、ほかの人がすでに見つけていました。</p>
                @break

            @case(ClaimResult::LimitReached)
                <x-chest class="mx-auto mt-2 h-28 w-28 opacity-40 grayscale" />
                <h1 class="mt-3 text-2xl font-black">獲得上限です</h1>
                <p class="mt-2 text-ink-soft">この子は獲得上限（{{ $prize->event->prizeLimitLabel() }}）に達しています。</p>
                @break

            @case(ClaimResult::NotStarted)
                <x-chest class="mx-auto mt-2 h-28 w-28 opacity-40 grayscale" />
                <h1 class="mt-3 text-2xl font-black">まだスタートしていません</h1>
                <p class="mt-2 text-ink-soft">イベントのページで「宝探しスタート」を押すと、宝箱を開けられるようになります。</p>
                @break

            @case(ClaimResult::TimeUp)
                <x-chest class="mx-auto mt-2 h-28 w-28 opacity-40 grayscale" />
                <h1 class="mt-3 text-2xl font-black">宝探しは終了しました</h1>
                <p class="mt-2 text-ink-soft">制限時間が過ぎたため、この宝箱は開けられません。</p>
                @break

            @case(ClaimResult::Miss)
                <x-chest class="mx-auto mt-2 h-28 w-28 grayscale" :open="true" />
                <h1 class="mt-3 text-3xl font-black text-ruby">ハズレ！</h1>
                <p class="mt-2 whitespace-pre-line text-ink-soft">{{ $prize->description ?: '残念！ほかのお宝を探そう。' }}</p>
                @break

            @default
                <x-chest class="mx-auto mt-2 h-36 w-36" />
                <h1 class="mt-1 text-2xl font-black">宝箱を見つけた！</h1>
                <p class="mt-2 text-ink-soft">子どもと一緒に開けてください。</p>
                <form method="POST" action="{{ route('cards.claim', [$card->card_token, $prize->token]) }}" class="mt-6" data-wait-for-connection>
                    @csrf
                    <button type="submit" class="btn-primary w-full py-4 text-xl">開ける！</button>
                </form>
        @endswitch

        @if ($result !== null)
            <div class="mt-8 flex flex-col gap-3">
                @if ($limitReached && $result === ClaimResult::Won)
                    <p class="rounded-xl bg-gold/20 px-3 py-2 text-sm font-bold">これでこの子は獲得上限（{{ $prize->event->prizeLimitLabel() }}）です。</p>
                @endif
                <a href="{{ route('cards.scan') }}" class="btn-primary w-full gap-2"><x-icon name="camera" class="h-5 w-5 shrink-0" />次の子のカードを読み取る</a>
                <a href="{{ route('cards.play', $card->card_token) }}" class="btn-secondary w-full">同じ子で、次のお宝を読み取る</a>
            </div>
        @endif
    </div>
</x-layouts.app>
