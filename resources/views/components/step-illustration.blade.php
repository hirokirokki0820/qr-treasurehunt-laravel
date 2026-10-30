@props(['name'])

{{-- トップページの「1〜3」の説明欄に入れる小さなイラスト --}}
<svg viewBox="0 0 240 150" role="img" {{ $attributes->merge(['class' => 'h-auto w-full']) }}>
    @switch($name)
        @case('register')
            <title>景品リストに景品を登録しているイラスト</title>
            {{-- 景品リストの用紙 --}}
            <rect x="34" y="18" width="116" height="122" rx="10" fill="#1f2a44" />
            <rect x="28" y="12" width="116" height="122" rx="10" fill="#ffffff" stroke="#1f2a44" stroke-width="3" />
            <rect x="62" y="4" width="48" height="16" rx="5" fill="#d9971c" stroke="#1f2a44" stroke-width="3" />
            <g stroke="#1f2a44" stroke-width="3" stroke-linecap="round">
                <rect x="42" y="34" width="14" height="14" rx="3" fill="#2c6e6a" stroke-width="2.5" />
                <path d="M45.5 41l3 3 5-6" stroke="#ffffff" stroke-width="2.5" fill="none" />
                <path d="M64 41h62" stroke-opacity=".7" />
                <rect x="42" y="60" width="14" height="14" rx="3" fill="#2c6e6a" stroke-width="2.5" />
                <path d="M45.5 67l3 3 5-6" stroke="#ffffff" stroke-width="2.5" fill="none" />
                <path d="M64 67h48" stroke-opacity=".7" />
                <rect x="42" y="86" width="14" height="14" rx="3" fill="#ffffff" stroke-width="2.5" />
                <path d="M64 93h40" stroke-opacity=".25" stroke-dasharray="2 6" />
            </g>
            {{-- 書き込み中のペン --}}
            <g transform="rotate(35 112 104)">
                <rect x="104" y="64" width="14" height="48" rx="3" fill="#d9971c" stroke="#1f2a44" stroke-width="3" />
                <path d="M104 112l7 14 7-14Z" fill="#fbf5e6" stroke="#1f2a44" stroke-width="3" stroke-linejoin="round" />
                <rect x="104" y="64" width="14" height="10" rx="3" fill="#b9402b" stroke="#1f2a44" stroke-width="3" />
            </g>
            {{-- 追加するプレゼント --}}
            <g>
                <rect x="162" y="78" width="58" height="50" rx="5" fill="#b9402b" stroke="#1f2a44" stroke-width="3" />
                <rect x="156" y="66" width="70" height="16" rx="4" fill="#d75a40" stroke="#1f2a44" stroke-width="3" />
                <path d="M191 66v62" stroke="#f6c453" stroke-width="8" />
                <path d="M191 66c-18-20-30-4-14 0M191 66c18-20 30-4 14 0" fill="none" stroke="#1f2a44" stroke-width="3" stroke-linecap="round" />
            </g>
            <circle cx="212" cy="50" r="13" fill="#2c6e6a" stroke="#1f2a44" stroke-width="3" />
            <path d="M212 43v14M205 50h14" stroke="#ffffff" stroke-width="3" stroke-linecap="round" />
            @break

        @case('hide')
            <title>QRコードを植木鉢の陰に隠しているイラスト</title>
            {{-- 置き場所を示す矢印 --}}
            <path d="M34 60c10-34 50-50 92-38" fill="none" stroke="#b9402b" stroke-width="3" stroke-dasharray="3 8" stroke-linecap="round" />
            <path d="M118 14l12 8-12 8" fill="none" stroke="#b9402b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
            {{-- 葉っぱの陰から少しのぞくQRコードのカード --}}
            <g transform="rotate(10 172 50)">
                <rect x="140" y="14" width="64" height="64" rx="6" fill="#ffffff" stroke="#1f2a44" stroke-width="3" />
                <g fill="#1f2a44">
                    <rect x="148" y="22" width="16" height="16" rx="2" />
                    <rect x="180" y="22" width="16" height="16" rx="2" />
                    <rect x="148" y="54" width="16" height="16" rx="2" />
                    <rect x="152" y="26" width="8" height="8" fill="#ffffff" />
                    <rect x="184" y="26" width="8" height="8" fill="#ffffff" />
                    <rect x="152" y="58" width="8" height="8" fill="#ffffff" />
                    <rect x="180" y="54" width="7" height="7" />
                    <rect x="189" y="63" width="7" height="7" />
                    <rect x="169" y="28" width="5" height="5" />
                </g>
            </g>
            {{-- 植木鉢と葉っぱ（QRを隠す） --}}
            <g stroke="#1f2a44" stroke-width="3" stroke-linejoin="round" transform="translate(-14 0)">
                <path d="M96 92c-14-26 4-46 22-40-2-18 26-26 34-6 18-12 40 4 30 26 18 4 18 30-2 30H100c-16 0-22-10-4-10Z" fill="#3d8a84" />
                <path d="M128 70c6 10 6 20 0 32M164 66c-4 12-2 24 6 34" fill="none" stroke="#2c6e6a" stroke-linecap="round" />
                <path d="M100 104h96l-10 38h-76Z" fill="#cf7c35" />
                <path d="M96 100h104v12H96Z" fill="#b8692a" />
            </g>
            {{-- ひみつ感のきらきら --}}
            <path d="M222 92l3 7 7 3-7 3-3 7-3-7-7-3 7-3Z" fill="#d9971c" />
            <path d="M44 104l3 7 7 3-7 3-3 7-3-7-7-3 7-3Z" fill="#d9971c" />
            @break

        @case('scan')
            <title>スマホをQRコードにかざして読み取っているイラスト</title>
            {{-- 壁に貼られたQRコード --}}
            <rect x="152" y="22" width="68" height="68" rx="6" fill="#ffffff" stroke="#1f2a44" stroke-width="3" />
            <g fill="#1f2a44">
                <rect x="160" y="30" width="16" height="16" rx="2" />
                <rect x="196" y="30" width="16" height="16" rx="2" />
                <rect x="160" y="66" width="16" height="16" rx="2" />
                <rect x="164" y="34" width="8" height="8" fill="#ffffff" />
                <rect x="200" y="34" width="8" height="8" fill="#ffffff" />
                <rect x="164" y="70" width="8" height="8" fill="#ffffff" />
                <rect x="196" y="66" width="7" height="7" />
                <rect x="205" y="75" width="7" height="7" />
                <rect x="183" y="36" width="6" height="6" />
                <rect x="183" y="58" width="6" height="12" />
            </g>
            {{-- 読み取りの光 --}}
            <path d="M118 66 152 30M118 86 152 86" stroke="#d9971c" stroke-width="3" stroke-linecap="round" stroke-dasharray="2 6" />
            {{-- スマホを持つ手 --}}
            <g transform="rotate(-10 84 84)">
                <rect x="54" y="26" width="64" height="112" rx="12" fill="#1f2a44" />
                <rect x="61" y="38" width="50" height="86" rx="5" fill="#ffffff" />
                <path d="M66 48v-6h6M106 48v-6h-6M66 112v6h6M106 112v6h-6" fill="none" stroke="#d9971c" stroke-width="3" stroke-linecap="round" />
                <g fill="#1f2a44">
                    <rect x="72" y="56" width="11" height="11" rx="1.5" />
                    <rect x="90" y="56" width="11" height="11" rx="1.5" />
                    <rect x="72" y="80" width="11" height="11" rx="1.5" />
                    <rect x="90" y="80" width="6" height="6" />
                    <rect x="96" y="86" width="5" height="5" />
                </g>
                <rect x="64" y="74" width="44" height="3" rx="1.5" fill="#d9971c" />
                {{-- 手 --}}
                <path d="M44 104c-2-14 8-22 16-16l4 4V80c0-6 10-6 10 0v40c0 18-10 26-24 26H40c-8 0-12-6-8-14Z" fill="#f2c9a0" stroke="#1f2a44" stroke-width="3" stroke-linejoin="round" />
                <path d="M118 98c8-2 12 6 6 10l-6 4M118 112c8-2 12 6 6 10l-6 4" fill="#f2c9a0" stroke="#1f2a44" stroke-width="3" stroke-linejoin="round" />
            </g>
            <path d="M30 30l3 7 7 3-7 3-3 7-3-7-7-3 7-3Z" fill="#d9971c" />
            @break
    @endswitch
</svg>
