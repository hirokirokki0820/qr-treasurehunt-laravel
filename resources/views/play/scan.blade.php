<x-layouts.app title="QRコードを読み取る">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'マイページ', 'url' => route('play.index')], ['label' => 'QRコードを読み取る']]" />
    <h1 class="mt-3 text-2xl font-black">QRコードを読み取る</h1>

    @if (! $timer->acceptsTreasures())
        <x-play-timer :event="$event" />
        <a href="{{ route('play.index') }}" class="btn-secondary mt-4 w-full">マイページに戻る</a>
    @elseif ($limitReached)
        <x-limit-reached :event="$event" class="mt-4" />
        <a href="{{ route('play.index') }}" class="btn-secondary mt-4 w-full">獲得した景品を見る</a>
    @else
        <x-play-timer :event="$event" :compact="true" />
        <x-qr-scanner accept="treasure" :autostart="true" class="mt-4" />
    @endif
</x-layouts.app>
