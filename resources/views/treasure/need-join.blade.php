<x-layouts.app title="お宝を発見！">
    <div class="card mx-auto max-w-md text-center">
        <x-chest class="mx-auto h-24 w-24" />
        <h1 class="mt-3 text-xl font-black">お宝 No.{{ $prize->number }} を発見！</h1>
        <p class="mt-2 text-ink-soft">宝箱を開けるには、先に参加受付が必要です。主催者が用意した「参加用QRコード」を読み取ってから、もう一度このQRコードを読み取ってください。</p>
    </div>
</x-layouts.app>
