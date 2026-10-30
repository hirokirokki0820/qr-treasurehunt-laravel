@props(['open' => false])

{{-- 宝箱のイラスト。open のときはふたが開いて光る --}}
<svg viewBox="0 0 64 64" fill="none" aria-hidden="true" {{ $attributes }}>
    @if ($open)
        <circle cx="32" cy="30" r="24" fill="#f6c453" opacity=".35" />
        <path d="M32 6v8M14 14l5 5M50 14l-5 5M8 30h7M56 30h-7" stroke="#d9971c" stroke-width="3" stroke-linecap="round" />
        <path d="M10 32 16 18h32l6 14Z" fill="#b8692a" stroke="#1f2a44" stroke-width="3" stroke-linejoin="round" />
    @else
        <path d="M10 30c0-9 6-14 22-14s22 5 22 14Z" fill="#b8692a" stroke="#1f2a44" stroke-width="3" stroke-linejoin="round" />
    @endif
    <rect x="10" y="30" width="44" height="24" rx="3" fill="#cf7c35" stroke="#1f2a44" stroke-width="3" />
    <path d="M10 38h44" stroke="#1f2a44" stroke-width="3" />
    <rect x="27" y="33" width="10" height="11" rx="2" fill="#f6c453" stroke="#1f2a44" stroke-width="2.5" />
    <circle cx="32" cy="38" r="1.6" fill="#1f2a44" />
</svg>
