@props(['event'])

{{--
    当日つながらなくなったときの「紙に切り替えた」。切り替えたあとは目立つ案内を出し、
    切り替える前は、ふだんの操作のじゃまにならないよう小さくたたんでおく。
--}}
@php($helpUrl = rtrim((string) config('services.kobapp.url'), '/').'/help/qr-takarasagashi/trouble#switch')
@if ($event->isPaperMode())
    <section class="mt-6 rounded-2xl border-2 border-ink bg-gold/30 px-5 py-4 shadow-[4px_4px_0_0_var(--color-ink)]" role="status" data-paper-mode>
        <p class="inline-flex rounded-full bg-ink px-2.5 py-0.5 text-xs font-bold text-white">紙で進行中（{{ $event->paper_mode_at->format('H:i') }}から）</p>
        <p class="mt-2 text-lg font-black">参加者のアプリでは、宝箱を開けられないようにしています</p>
        <ul class="mt-1 space-y-0.5 text-sm">
            <li>・見つけたQRカードを受付に持ってきてもらい、景品リストの「渡した人」に名前を書いて渡します</li>
            <li>・アプリで開けた子は、獲得した景品の画面を見せてもらいます</li>
            <li>・途中で直っても、最後まで紙で進めてください</li>
        </ul>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <a href="{{ route('events.prize-list', $event) }}" class="btn-primary text-sm"><x-icon name="printer" class="h-4 w-4" />景品リストを開く</a>
            <a href="{{ $helpUrl }}" class="text-sm font-bold text-sea underline" target="_blank" rel="noopener">紙で続ける手順</a>
        </div>
        <form method="POST" action="{{ route('events.paper.end', $event) }}" class="mt-4 border-t-2 border-dashed border-ink/20 pt-3" data-confirm="アプリに戻します。参加者は、また宝箱を開けられるようになります。紙で渡した景品はアプリに記録されていないので、同じ景品を二重に渡さないよう気をつけてください。よろしいですか？">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-bold text-ink-soft underline">押し間違えたので、アプリに戻す</button>
        </form>
    </section>
@else
    <details class="mt-6 rounded-2xl border-2 border-dashed border-ink/30 bg-white/60 [&_summary::-webkit-details-marker]:hidden" data-paper-switch>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 text-sm font-bold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea">
            <span>全員がつながらなくなったとき（緊急時）</span>
            <span class="shrink-0 rounded-full bg-ink/10 px-3 py-1 text-xs" aria-hidden="true">開く／閉じる</span>
        </summary>
        <div class="border-t-2 border-dashed border-ink/15 px-5 pt-3 pb-4 text-sm">
            <p>会場の回線や通信障害で全員がつながらなくなったら、紙に切り替えます。切り替えると、つながった子のアプリにも「QRカードを取って受付へ」と案内が出て、アプリでは宝箱を開けられなくなります（アプリと紙が混ざって、同じ景品を二重に渡さないため）。</p>
            <p class="mt-1 text-ink-soft">このボタンは、主催者のスマホがつながっているときに押せます。主催者もつながらないときは、押さずに<a href="{{ $helpUrl }}" class="font-bold text-sea underline" target="_blank" rel="noopener">紙で続ける手順</a>どおりに進めてください。</p>
            <form method="POST" action="{{ route('events.paper.start', $event) }}" class="mt-3" data-confirm="紙に切り替えます。参加者のアプリでは宝箱を開けられなくなり、「QRカードを取って受付へ」と案内が出ます。よろしいですか？">
                @csrf
                <button type="submit" class="btn-danger text-sm">紙に切り替えた</button>
            </form>
        </div>
    </details>
@endif
