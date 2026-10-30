@props(['name'])

{{-- ボタン用の線画アイコン。文字色（currentColor）に合わせて表示される --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }}>
    @switch($name)
        @case('printer')
            <path d="M6 9V3h12v6" />
            <rect x="3" y="9" width="18" height="8" rx="2" />
            <path d="M6 14h12v7H6z" />
            <path d="M17 12h.01" />
            @break
        @case('camera')
            <path d="M14.5 4h-5L7.5 6.5H5A2 2 0 0 0 3 8.5v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-2.5Z" />
            <circle cx="12" cy="13" r="3.5" />
            @break
        @case('qr-code')
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3" />
            @break
        @case('trash')
            <path d="M3 6h18" />
            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
            <path d="M19 6l-1 14a2 2 0 0 1-2 1H8a2 2 0 0 1-2-1L5 6" />
            <path d="M10 11v6M14 11v6" />
            @break
        @case('select')
            <rect x="3" y="3" width="18" height="18" rx="3" />
            <path d="m8 12 3 3 5-6" />
            @break
        @case('alert')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7.5v5.5" />
            <path d="M12 16.5h.01" />
            @break
        @case('stop')
            <circle cx="12" cy="12" r="9" />
            <rect x="9" y="9" width="6" height="6" rx="1" />
            @break
        @case('refresh')
            <path d="M21 12a9 9 0 1 1-3-6.7" />
            <path d="M21 3v6h-6" />
            @break
        @case('reset')
            <path d="M3 12a9 9 0 1 0 3-6.7" />
            <path d="M3 3v6h6" />
            @break
    @endswitch
</svg>
