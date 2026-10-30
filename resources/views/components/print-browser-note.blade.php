{{-- 印刷ページの注意書き。ブラウザによって仕上がりが変わるため Chrome での印刷を勧める --}}
<div {{ $attributes->merge(['class' => 'no-print flex items-start gap-2.5 rounded-xl border-2 border-sea/40 bg-sea/10 px-3 py-2.5 text-sm text-ink']) }} role="note">
    <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-sea" />
    <p>
        <span class="font-bold text-sea">印刷は Google Chrome がおすすめです。</span><br>
        Safari などほかのブラウザでは、レイアウトが崩れたり見切れたりすることがあります。
    </p>
</div>
