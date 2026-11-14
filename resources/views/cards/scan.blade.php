<x-layouts.app title="参加者カードを読み取る">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => '参加者カードを読み取る']]" />
    <h1 class="mt-3 text-2xl font-black">参加者カードを読み取る</h1>
    <p class="mt-1 text-sm text-ink-soft">お宝を持ってきた子の「参加者カード」を読み取ると、その子の番になります。続けてお宝QRを読み取ってください。</p>

    <x-qr-scanner accept="card" :autostart="true" class="mt-4" />
</x-layouts.app>
