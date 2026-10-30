<x-layouts.app title="引き換えQRを読み取る">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => '引き換えQRを読み取る']]" />
    <h1 class="mt-3 text-2xl font-black">引き換えQRを読み取る</h1>
    <p class="mt-1 text-sm text-ink-soft">参加者のマイページにある「引き換え用QRコード」を読み取ると、その人の獲得景品が開きます。</p>

    <x-qr-scanner accept="redeem" :autostart="true" class="mt-4" />
</x-layouts.app>
