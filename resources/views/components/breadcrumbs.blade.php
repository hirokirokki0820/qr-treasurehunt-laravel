@props(['items'])

{{--
    パンくずリスト。items は [['label' => 表示名, 'url' => リンク先], ...] の配列で、最後の要素が今いるページ（url なし）。
    スマホで収まらないときは横にスクロールでき、開いたときは今いるページ（右端）が見える位置になる。
--}}
<nav aria-label="パンくずリスト" data-breadcrumbs {{ $attributes->merge(['class' => 'no-print overflow-x-auto rounded-xl border-2 border-ink/80 bg-white/80 text-sm shadow-[3px_3px_0_0_var(--color-ink)]']) }}>
    <ol class="flex w-max min-w-full items-stretch">
        @foreach ($items as $item)
            @if (! $loop->first)
                <li aria-hidden="true" class="flex items-center text-ink/40">›</li>
            @endif

            @if ($loop->last)
                <li aria-current="page" class="flex items-center gap-1.5 bg-gold/30 px-3 py-2 font-black whitespace-nowrap text-ink">
                    @if ($loop->first)
                        <x-chest class="h-5 w-5 shrink-0" />
                    @endif
                    {{ $item['label'] }}
                </li>
            @else
                <li class="flex items-center">
                    <a href="{{ $item['url'] }}" class="flex items-center gap-1.5 self-stretch px-3 py-2 font-bold whitespace-nowrap text-sea transition hover:bg-gold/20">
                        @if ($loop->first)
                            <x-chest class="h-5 w-5 shrink-0" />
                        @endif
                        {{ $item['label'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
