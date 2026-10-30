@php
    use App\Enums\QrPrintSize;

    // 印刷はブラウザ（Chrome・Safari）ごとの違いが出ないよう、A4の1枚分を mm 単位の固定サイズで組む。
    // 余白がブラウザ既定（15mm 程度）になっても収まるよう、180mm × 262mm の中に並べている。
    $styles = match ($size) {
        QrPrintSize::Large => [
            'grid' => 'grid-cols-2 gap-4 sm:grid-cols-3 print:grid-cols-3 print:grid-rows-3 print:gap-[4mm]',
            'card' => 'rounded-2xl p-4 print:rounded-[4mm] print:p-[4mm]',
            'event' => 'text-xs print:text-[9pt]',
            'number' => 'text-xl print:text-[16pt]',
            'qr' => 'mt-2 max-w-48 print:mt-[2mm] print:w-[44mm]',
            'hint' => 'mt-2 text-xs print:mt-[2mm] print:text-[8pt]',
        ],
        QrPrintSize::Medium => [
            'grid' => 'grid-cols-3 gap-3 sm:grid-cols-4 print:grid-cols-4 print:grid-rows-4 print:gap-[3mm]',
            'card' => 'rounded-xl p-3 print:rounded-[3mm] print:p-[3mm]',
            'event' => 'text-[10px] print:text-[7pt]',
            'number' => 'text-base print:text-[12pt]',
            'qr' => 'mt-1.5 max-w-36 print:mt-[1.5mm] print:w-[32mm]',
            'hint' => 'mt-1.5 text-[10px] whitespace-nowrap print:mt-[1.5mm] print:text-[6.5pt] print:tracking-tight',
        ],
        QrPrintSize::Small => [
            'grid' => 'grid-cols-4 gap-2 sm:grid-cols-5 print:grid-cols-5 print:grid-rows-6 print:gap-[2mm]',
            'card' => 'rounded-lg p-2 print:rounded-[2mm] print:p-[2mm]',
            'event' => 'hidden',
            'number' => 'text-sm print:text-[10pt]',
            'qr' => 'mt-1 max-w-28 print:mt-[1mm] print:w-[26mm]',
            'hint' => 'hidden',
        ],
    };
@endphp

<x-layouts.app :title="'お宝用QRコード - '.$event->name" :wide="true">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>

    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => 'お宝用QRコード印刷']]" />
    <x-print-browser-note class="mb-4" />
    <div class="no-print mb-6 flex flex-wrap items-center justify-end gap-3">
        <a href="{{ route('events.show', $event) }}" class="btn-secondary">キャンセル</a>
        <button type="button" onclick="window.print()" class="btn-primary"><x-icon name="printer" />印刷する</button>
    </div>

    <div class="no-print card mb-6 space-y-4 text-sm">
        <p>QRコードには景品名を載せていないので、当たりかハズレかは開けるまで分かりません。印刷して切り取り、会場に隠してください。</p>

        <div>
            <p class="label">印刷サイズ（A4用紙1枚あたり）</p>
            <nav class="grid grid-cols-3 gap-2 sm:inline-grid sm:w-auto" aria-label="印刷サイズ">
                @foreach ($sizes as $option)
                    <a
                        href="{{ route('events.qrcodes', [$event, 'size' => $option->value]) }}"
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
            @if ($size === QrPrintSize::Small)
                <p class="mt-2 text-xs text-ink-soft">小サイズはスペースを優先して、イベント名と案内文を省いています。読み取りやすさのため、明るい場所に貼るのがおすすめです。</p>
            @endif
        </div>
    </div>

    @if ($prizes->isEmpty())
        <div class="card text-center">まだ景品がありません。</div>
    @else
        @foreach ($prizes->chunk($size->perSheet()) as $sheet)
            {{-- A4用紙1枚分。印刷時は固定サイズにして、1枚ごとに改ページする --}}
            <div @class([
                'mb-3 grid print:mx-auto print:mb-0 print:h-[262mm] print:w-[180mm] print:overflow-hidden',
                'print:break-after-page' => ! $loop->last,
                $styles['grid'],
            ])>
                @foreach ($sheet as $prize)
                    <div @class(['flex min-w-0 flex-col items-center justify-center border-2 border-dashed border-ink bg-white text-center print:border-[0.4mm]', $styles['card']])>
                        <p @class(['w-full truncate font-bold tracking-widest text-ink-soft', $styles['event']])>{{ $event->name }}</p>
                        <p @class(['font-black', $styles['number']])>お宝 No.{{ $prize->number }}</p>
                        <div @class(['mx-auto aspect-square w-full print:max-w-none', $styles['qr']]) data-qr="{{ route('treasure.show', $prize) }}"></div>
                        <p @class(['text-ink-soft', $styles['hint']])>スマホのカメラで読み取ってね</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</x-layouts.app>
