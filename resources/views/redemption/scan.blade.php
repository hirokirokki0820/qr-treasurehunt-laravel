<x-layouts.app title="引き換えQRを読み取る">
    <x-breadcrumbs class="mb-4" :items="array_values(array_filter([['label' => 'イベント一覧', 'url' => route('events.index')], $event ? ['label' => $event->name, 'url' => route('events.show', $event)] : null, ['label' => '引き換えQRを読み取る']]))" />
    <h1 class="mt-3 text-2xl font-black">引き換えQRを読み取る</h1>
    <p class="mt-1 text-sm text-ink-soft">参加者のマイページにある「引き換え用QRコード」を読み取ると、その人の獲得景品が開きます。「参加者カード」を読み取ると、その子の番になり、獲得した景品の確認と、持ってきたお宝の読み取りができます。</p>

    <x-qr-scanner accept="redeem" :autostart="true" class="mt-4" />

    @if ($event)
        <a href="{{ route('events.show', $event) }}" class="btn-secondary mt-4 w-full">イベントのページに戻る</a>
    @endif
</x-layouts.app>
