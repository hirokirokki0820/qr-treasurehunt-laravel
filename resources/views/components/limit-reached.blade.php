@props(['event'])

{{-- 獲得上限に達したときのお知らせ --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl bg-ink px-5 py-5 text-center text-white']) }} role="status">
    <p class="text-lg font-black">獲得上限に達しました</p>
    <p class="mt-1 text-sm text-white/80">このイベントのお宝は{{ $event->prizeLimitLabel() }}です。<br>これ以上QRコードは読み取れません。</p>
    <p class="mt-3 text-sm font-bold text-gold">獲得した景品は、受付で交換してね！</p>
</div>
