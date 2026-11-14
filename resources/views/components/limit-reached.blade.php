@props(['event', 'redeemLink' => false])

{{-- 獲得上限に達したときのお知らせ --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl bg-ink px-5 py-5 text-center text-white']) }} role="status">
    <p class="text-lg font-black">獲得上限に達しました</p>
    <p class="mt-1 text-sm text-white/80">このイベントのお宝は{{ $event->prizeLimitLabel() }}です。<br>これ以上、お宝の宝箱は開けられません。</p>
    <p class="mt-3 text-sm font-bold text-gold">獲得した景品は、受付で交換してね！</p>
    @if ($redeemLink)
        <a href="#redeem-qr" class="mt-3 inline-flex min-h-11 items-center justify-center rounded-xl border-2 border-white bg-gold px-5 py-2 font-black text-ink">引き換えQRを表示</a>
    @endif
</div>
