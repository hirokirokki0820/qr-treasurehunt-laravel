<x-layouts.app title="宝探しに参加する" :for-guests="true">
    <div class="mx-auto max-w-md text-center">
        <x-chest class="mx-auto h-20 w-20" />
        <h1 class="mt-3 text-2xl font-black">{!! nl2br(e($heading)) !!}</h1>
        <p class="mt-2 text-ink-soft text-pretty">{{ $lead }}</p>
    </div>

    <div class="mx-auto mt-6 max-w-md">
        <x-qr-scanner accept="join" start-label="カメラで読み取る" />
    </div>
</x-layouts.app>
