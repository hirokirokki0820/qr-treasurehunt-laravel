<x-layouts.app title="参加者カードを読み取る">
    <x-breadcrumbs class="mb-4" :items="array_values(array_filter([['label' => 'イベント一覧', 'url' => route('events.index')], $event ? ['label' => $event->name, 'url' => route('events.show', $event)] : null, $event ? ['label' => '参加者カード', 'url' => route('cards.index', $event)] : null, ['label' => '参加者カードを読み取る']]))" />
    <h1 class="mt-3 text-2xl font-black">参加者カードを読み取る</h1>
    <p class="mt-1 text-sm text-ink-soft">お宝を持ってきた子の「参加者カード」を読み取ると、その子の番になります。続けてお宝QRを読み取ってください。</p>

    <x-qr-scanner accept="card" :autostart="true" class="mt-4" />

    @if ($event)
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('cards.index', $event) }}" class="btn-secondary w-full">参加者カードのページに戻る</a>
            <a href="{{ route('events.show', $event) }}" class="btn-secondary w-full">イベントのページに戻る</a>
        </div>
    @endif
</x-layouts.app>
