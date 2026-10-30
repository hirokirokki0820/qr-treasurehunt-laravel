@php
    use App\Enums\JoinQrPrintSize;

    $print = match ($size) {
        JoinQrPrintSize::Large => [
            'sheet' => 'print:flex print:h-[262mm] print:w-[180mm]',
            'card' => 'print:h-full print:w-full print:rounded-[8mm] print:border-[1.5mm] print:p-[12mm]',
            'label' => 'print:text-2xl',
            'title' => 'print:mt-[3mm] print:text-5xl print:leading-tight',
            'qr' => 'print:mt-[10mm] print:w-[150mm]',
            'message' => 'print:mt-[10mm] print:text-3xl print:leading-snug',
        ],
        JoinQrPrintSize::Medium => [
            'sheet' => 'print:flex print:h-[262mm] print:w-[180mm] print:items-center print:justify-center',
            'card' => 'print:h-[200mm] print:w-[150mm] print:rounded-[6mm] print:border-[1.2mm] print:p-[10mm]',
            'label' => 'print:text-xl',
            'title' => 'print:mt-[2mm] print:text-4xl print:leading-tight',
            'qr' => 'print:mt-[8mm] print:w-[110mm]',
            'message' => 'print:mt-[8mm] print:text-2xl print:leading-snug',
        ],
        JoinQrPrintSize::Small => [
            'sheet' => 'print:grid print:h-[175mm] print:w-[262mm] print:grid-cols-2 print:gap-[8mm]',
            'card' => 'print:h-full print:w-full print:rounded-[5mm] print:border-[1mm] print:p-[8mm]',
            'label' => 'print:text-base',
            'title' => 'print:mt-[1mm] print:text-3xl print:leading-tight',
            'qr' => 'print:mt-[6mm] print:w-[85mm]',
            'message' => 'print:mt-[6mm] print:text-xl print:leading-snug',
        ],
    };
@endphp

<x-layouts.app :title="'参加用QRコード - '.$event->name">
    <style>
        @page {
            size: A4 {{ $size->orientation() }};
            margin: 10mm;
        }
    </style>

    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '参加用QRコード']]" />
    <x-print-browser-note class="mb-4" />
    <div class="no-print mb-6 flex flex-wrap items-center justify-end gap-3">
        <a href="{{ route('events.show', $event) }}" class="btn-secondary">キャンセル</a>
        <button type="button" onclick="window.print()" class="btn-primary"><x-icon name="printer" />印刷する</button>
    </div>

    <div class="no-print card mb-6 text-sm">
        <p class="label">印刷サイズ</p>
        <nav class="grid grid-cols-3 gap-2 sm:inline-grid" aria-label="印刷サイズ">
            @foreach ($sizes as $option)
                <a
                    href="{{ route('events.join-qrcode', [$event, 'size' => $option->value]) }}"
                    @class([
                        'rounded-xl border-2 px-4 py-2 text-center transition',
                        'border-ink bg-gold font-black shadow-[3px_3px_0_0_var(--color-ink)]' => $option === $size,
                        'border-ink/30 bg-white font-bold text-ink-soft hover:border-ink hover:text-ink' => $option !== $size,
                    ])
                    @if ($option === $size) aria-current="true" @endif
                >
                    <span class="block text-base">{{ $option->label() }}</span>
                    <span class="block text-xs">{{ $option->description() }}</span>
                </a>
            @endforeach
        </nav>
        @if ($size->copiesPerSheet() > 1)
            <p class="mt-2 text-xs text-ink-soft">印刷すると、A4横向きの用紙に同じQRコードが{{ $size->copiesPerSheet() }}枚並びます。真ん中で切り分けて使ってください。<br>Safariで印刷するときは、印刷画面で用紙の向きを「横」にしてください。</p>
        @endif
    </div>

    {{-- 印刷時は A4 1枚分の固定サイズ（ブラウザ既定の余白でも収まる大きさ）で中央に置く --}}
    <div @class(['print:mx-auto print:overflow-hidden', $print['sheet']])>
        @for ($copy = 0; $copy < $size->copiesPerSheet(); $copy++)
            <div @class([
                'card mx-auto max-w-md text-center print:flex print:max-w-none print:flex-col print:items-center print:justify-center print:border-dashed print:bg-white print:shadow-none',
                $print['card'],
                'hidden' => $copy > 0,
            ])>
                <p @class(['text-sm font-bold tracking-widest text-ink-soft', $print['label']])>参加受付</p>
                <h1 @class(['mt-1 text-2xl font-black', $print['title']])>{{ $event->name }}</h1>
                <div @class(['mx-auto mt-5 aspect-square w-full max-w-72 print:max-w-none', $print['qr']]) data-qr="{{ route('join', $event) }}"></div>
                <p @class(['mt-4 font-bold', $print['message']])>このQRコードを読み取って<br>宝探しに参加しよう！</p>
                <p class="no-print mt-4 break-all rounded-lg bg-parchment px-3 py-2 text-left text-xs text-ink-soft">{{ route('join', $event) }}</p>
            </div>
        @endfor
    </div>
</x-layouts.app>
