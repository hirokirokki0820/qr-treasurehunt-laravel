<x-layouts.app title="別のイベントのお宝です">
    <div class="card mx-auto max-w-md text-center">
        <x-chest class="mx-auto h-28 w-28 opacity-40 grayscale" />
        <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-ruby/10 px-3 py-1 text-sm font-bold text-ruby">
            <x-icon name="alert" class="h-4 w-4" />
            {{ $isSameOrganizer ? 'イベントが違います' : '主催者が違います' }}
        </p>
        <h1 class="mt-3 text-xl font-black">このお宝は開けられません</h1>
        <p class="mt-2 text-ink-soft">
            @if ($isSameOrganizer)
                このQRコードは、いま参加しているイベントとは<strong class="text-ink">別のイベント</strong>のお宝です。
            @else
                このQRコードは、<strong class="text-ink">別の主催者のイベント</strong>のお宝です。
            @endif
        </p>

        <div class="mt-5 rounded-xl bg-gold/15 px-4 py-3 text-left">
            <p class="text-xs font-bold text-ink-soft">あなたが参加中のイベント</p>
            <p class="mt-0.5 font-black">{{ $currentEvent->name }}</p>
        </div>
        <p class="mt-3 text-sm text-ink-soft">このイベントのお宝QRコードを探してね！</p>

        <div class="mt-8 flex flex-col gap-3">
            @unless ($limitReached)
                <a href="{{ route('play.scan') }}" class="btn-primary w-full"><x-icon name="camera" />次のQRコードを読み取る</a>
            @endunless
            <a href="{{ route('play.index') }}" class="btn-secondary w-full">マイページに戻る</a>
        </div>
    </div>
</x-layouts.app>
