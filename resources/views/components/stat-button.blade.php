@props(['href', 'label', 'value', 'unit', 'action', 'active' => false])

{{-- イベントページ上部の数字の枠。押せるボタンとして使う --}}
<a
    href="{{ $href }}"
    @class([
        'card group flex items-end justify-between gap-3 py-4 transition hover:-translate-y-0.5 hover:bg-gold/25 hover:shadow-[6px_6px_0_0_var(--color-ink)] active:translate-y-0 active:shadow-[2px_2px_0_0_var(--color-ink)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea',
        'bg-gold/25 ring-2 ring-gold-deep/60' => $active,
    ])
    @if ($active) aria-current="true" @endif
>
    <div>
        <p class="text-sm text-ink-soft">{{ $label }}</p>
        <p class="text-3xl font-black">{{ $value }}<span class="text-base"> {{ $unit }}</span></p>
    </div>
    @if ($active)
        <span class="mb-1 inline-flex shrink-0 items-center gap-1 rounded-full bg-sea px-3 py-1 text-xs font-bold text-white"><span aria-hidden="true">✓</span>表示中</span>
    @else
        <span class="mb-1 inline-flex shrink-0 items-center gap-1 rounded-full bg-ink px-3 py-1 text-xs font-bold text-white transition group-hover:bg-sea">{{ $action }}<span aria-hidden="true">›</span></span>
    @endif
</a>
