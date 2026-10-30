@props(['accept' => 'treasure', 'startLabel' => 'カメラを起動', 'autostart' => false])

@php
    $modes = [
        'treasure' => ['/treasure/', 'このQRコードは宝ではないようです。別のQRコードを探そう！', 'お宝を発見！宝箱へ向かいます…'],
        'join' => ['/join/', 'このQRコードは参加用ではないようです。主催者の「参加用QRコード」を読み取ってください。', '参加用QRコードを読み取りました。参加受付へ移動します…'],
        'redeem' => ['/host/redeem/', 'このQRコードは引き換え用ではないようです。参加者のマイページにある「引き換え用QRコード」を読み取ってください。', '引き換え用QRコードを読み取りました。参加者の獲得景品を開きます…'],
    ];
    [$acceptPath, $wrongMessage, $successMessage] = $modes[$accept] ?? $modes['treasure'];
@endphp

{{--
    アプリ内のQRスキャナー。accept で読み取るQRコードの種類を決める。
    treasure: 宝のQRコード（/treasure/…）、join: 参加用QRコード（/join/…）、redeem: 参加者の引き換え用QRコード（/host/redeem/…）
    autostart を付けると、ページを開いた時点でカメラを起動する（失敗したときはボタンから再度起動できる）。
--}}
<div
    id="qr-scanner"
    data-accept-path="{{ $acceptPath }}"
    data-wrong-message="{{ $wrongMessage }}"
    data-success-message="{{ $successMessage }}"
    data-autostart="{{ $autostart ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => 'card']) }}
>
    <div id="qr-reader" class="overflow-hidden rounded-xl bg-ink/5 [&_video]:w-full"></div>

    <p data-scanner-message hidden class="mt-4 rounded-xl px-3 py-2 text-sm font-bold data-[tone=error]:bg-ruby/10 data-[tone=error]:text-ruby data-[tone=info]:bg-sea/10 data-[tone=info]:text-sea data-[tone=success]:bg-gold/25 data-[tone=success]:text-ink" role="status"></p>

    <div class="mt-4 flex justify-center gap-3">
        <button type="button" data-scanner-start class="btn-primary w-full py-3 text-lg"><x-icon name="camera" class="h-6 w-6 shrink-0" />{{ $startLabel }}</button>
        <button type="button" data-scanner-stop hidden class="btn-secondary w-full py-3">停止</button>
    </div>
</div>

<p class="mt-4 text-center text-xs text-ink-soft">
    うまく起動しないときは、スマホ標準のカメラアプリでQRコードを読み取ってください。
</p>
