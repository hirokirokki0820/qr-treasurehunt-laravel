<x-layouts.app :title="'参加者カード印刷 - '.$event->name" :wide="true">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>

    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '参加者カード', 'url' => route('cards.index', $event)], ['label' => '印刷']]" />
    <x-print-browser-note class="mb-4" />
    <div class="no-print mb-6 flex flex-wrap items-center justify-end gap-3">
        <a href="{{ route('cards.index', $event) }}" class="btn-secondary">戻る</a>
        <button type="button" onclick="window.print()" class="btn-primary"><x-icon name="printer" />印刷する</button>
    </div>
    <p class="no-print card mb-6 text-sm">切り取って、子ども（またはチーム）に1枚ずつ渡してください。お宝QRと間違えないよう、名札として首から下げるのもおすすめです。</p>

    @if ($cards->isEmpty())
        <div class="card text-center">まだカードがありません。</div>
    @else
        @foreach ($cards->chunk(9) as $sheet)
            <div @class([
                'mb-3 grid grid-cols-2 gap-4 sm:grid-cols-3 print:mx-auto print:mb-0 print:h-[262mm] print:w-[180mm] print:grid-cols-3 print:grid-rows-3 print:gap-[4mm] print:overflow-hidden',
                'print:break-after-page' => ! $loop->last,
            ])>
                @foreach ($sheet as $card)
                    <div class="flex min-w-0 flex-col items-center justify-center rounded-2xl border-2 border-ink bg-white p-4 text-center print:rounded-[4mm] print:border-[0.6mm] print:p-[4mm]">
                        <p class="w-full truncate text-xs font-bold tracking-widest text-ink-soft print:text-[8pt]">参加者カード ・ {{ $event->name }}</p>
                        <p class="mt-1 w-full truncate text-xl font-black print:text-[16pt]">{{ $card->nickname }}</p>
                        <div class="mx-auto mt-2 aspect-square w-full max-w-40 print:w-[40mm]" data-qr="{{ route('cards.play', $card->card_token) }}"></div>
                        <p class="mt-2 text-xs text-ink-soft print:text-[7.5pt]">お宝を見つけたら、受付でこのカードを見せてね</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</x-layouts.app>
