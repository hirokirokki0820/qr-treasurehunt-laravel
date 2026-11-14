@php
    use App\Enums\CardPrintSize;

    // 印刷はブラウザごとの違いが出ないよう、A4の1枚分を mm 単位の固定サイズで組む（180mm × 262mm の中に並べる）
    $styles = match ($size) {
        CardPrintSize::Large => [
            'grid' => 'grid-cols-2 gap-4 print:grid-cols-2 print:grid-rows-2 print:gap-[6mm]',
            'card' => 'p-5 print:rounded-[5mm] print:p-[6mm]',
            'label' => 'text-sm print:text-[10pt]',
            'name' => 'text-3xl print:text-[26pt]',
            'qr' => 'max-w-56 print:w-[62mm]',
            'hint' => 'text-sm print:text-[10pt]',
        ],
        CardPrintSize::Medium => [
            'grid' => 'grid-cols-2 gap-4 sm:grid-cols-3 print:grid-cols-3 print:grid-rows-3 print:gap-[4mm]',
            'card' => 'p-4 print:rounded-[4mm] print:p-[4mm]',
            'label' => 'text-xs print:text-[8pt]',
            'name' => 'text-xl print:text-[16pt]',
            'qr' => 'max-w-40 print:w-[40mm]',
            'hint' => 'text-xs print:text-[7.5pt]',
        ],
    };
@endphp

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
    <div class="no-print card mb-6 space-y-4 text-sm">
        <p>切り取って、子ども（またはチーム）に1枚ずつ渡してください。お宝QRと間違えないよう、名札として首から下げるのもおすすめです。</p>
        <div>
            <p class="label">印刷サイズ（A4用紙1枚あたり）</p>
            <nav class="grid grid-cols-2 gap-2 sm:inline-grid sm:w-auto" aria-label="印刷サイズ">
                @foreach ($sizes as $option)
                    <a
                        href="{{ route('cards.print', [$event, 'size' => $option->value]) }}"
                        @class([
                            'rounded-xl border-2 px-4 py-2 text-center transition',
                            'border-ink bg-gold font-black shadow-[3px_3px_0_0_var(--color-ink)]' => $option === $size,
                            'border-ink/30 bg-white font-bold text-ink-soft hover:border-ink hover:text-ink' => $option !== $size,
                        ])
                        @if ($option === $size) aria-current="true" @endif
                    >
                        <span class="block text-base">{{ $option->label() }}</span>
                        <span class="block text-xs">{{ $option->layout() }}枚</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    @if ($cards->isEmpty())
        <div class="card text-center">まだカードがありません。</div>
    @else
        @foreach ($cards->chunk($size->perSheet()) as $sheet)
            <div @class([
                'mb-3 grid print:mx-auto print:mb-0 print:h-[262mm] print:w-[180mm] print:overflow-hidden',
                'print:break-after-page' => ! $loop->last,
                $styles['grid'],
            ])>
                @foreach ($sheet as $card)
                    <div @class(['flex min-w-0 flex-col items-center justify-center rounded-2xl border-2 border-ink bg-white text-center print:border-[0.6mm]', $styles['card']])>
                        <p @class(['w-full truncate font-bold tracking-widest text-ink-soft', $styles['label']])>参加者カード ・ {{ $event->name }}</p>
                        <p @class(['mt-1 w-full truncate font-black', $styles['name']])>{{ $card->nickname }}</p>
                        <div @class(['mx-auto mt-2 aspect-square w-full print:max-w-none', $styles['qr']]) data-qr="{{ route('cards.play', $card->card_token) }}"></div>
                        <p @class(['mt-2 text-ink-soft', $styles['hint']])>お宝を見つけたら、受付でこのカードを見せてね</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</x-layouts.app>
