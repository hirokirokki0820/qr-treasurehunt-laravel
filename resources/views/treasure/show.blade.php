@php
    use App\Enums\ClaimResult;
@endphp

<x-layouts.app :title="'お宝 No.'.$prize->number">
    <div class="card mx-auto max-w-md text-center">
        @switch($result)
            @case(ClaimResult::Won)
                <x-chest class="mx-auto h-36 w-36 motion-safe:animate-bounce" :open="true" />
                <p class="mt-2 text-sm font-black tracking-widest text-gold-deep">おめでとう！</p>
                <h1 class="mt-1 text-3xl font-black">{{ $prize->name }}</h1>
                <p class="mt-1 text-sm text-ink-soft">を手に入れた！</p>
                @if ($prize->description)
                    <p class="mt-4 whitespace-pre-line rounded-xl bg-gold/15 px-3 py-2">{{ $prize->description }}</p>
                @endif
                @break

            @case(ClaimResult::AlreadyMine)
                <x-chest class="mx-auto h-28 w-28" :open="true" />
                <h1 class="mt-3 text-2xl font-black">{{ $prize->name }}</h1>
                <p class="mt-2 text-ink-soft">このお宝はもうあなたのものです。</p>
                @break

            @case(ClaimResult::TakenByOther)
                <x-chest class="mx-auto h-28 w-28 opacity-50 grayscale" :open="true" />
                <h1 class="mt-3 text-2xl font-black">からっぽ…</h1>
                <p class="mt-2 text-ink-soft">このお宝は、ほかの人がすでに見つけていました。次のお宝を探そう！</p>
                @break

            @case(ClaimResult::LimitReached)
                <x-chest class="mx-auto h-28 w-28 opacity-40 grayscale" />
                <p class="mt-2 text-sm font-bold tracking-widest text-ink-soft">お宝 No.{{ $prize->number }}</p>
                <x-limit-reached :event="$prize->event" class="mt-4" />
                @break

            @case(ClaimResult::Miss)
                <x-chest class="mx-auto h-28 w-28 grayscale" :open="true" />
                <h1 class="mt-3 text-3xl font-black text-ruby">ハズレ！</h1>
                <p class="mt-2 whitespace-pre-line text-ink-soft">{{ $prize->description ?: '残念！ほかのお宝を探そう。' }}</p>
                @break

            @default
                <x-chest class="mx-auto h-36 w-36" />
                <p class="mt-2 text-sm font-bold tracking-widest text-ink-soft">お宝 No.{{ $prize->number }}</p>
                <h1 class="mt-1 text-2xl font-black">宝箱を見つけた！</h1>
                <p class="mt-2 text-ink-soft">中身は開けてみてのお楽しみ。</p>
                <form method="POST" action="{{ route('treasure.claim', $prize) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="btn-primary w-full py-4 text-xl">開ける！</button>
                </form>
        @endswitch

        @if ($result !== null)
            <div class="mt-8 flex flex-col gap-3">
                @if ($limitReached)
                    @if ($result !== ClaimResult::LimitReached)
                        <p class="rounded-xl bg-gold/20 px-3 py-2 text-sm font-bold">これで獲得上限（{{ $prize->event->prizeLimitLabel() }}）に達しました。受付で景品と交換してね！</p>
                    @endif
                @else
                    <a href="{{ route('play.scan') }}" class="btn-primary w-full"><x-icon name="camera" />次のQRコードを読み取る</a>
                @endif
                <a href="{{ route('play.index') }}" class="btn-secondary w-full">獲得した景品を見る</a>
            </div>
        @endif
    </div>
</x-layouts.app>
