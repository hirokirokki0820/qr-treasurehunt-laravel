<x-layouts.app :title="'景品リスト - '.$event->name" :wide="true">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
    </style>

    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '景品リスト印刷']]" />
    <x-print-browser-note class="mb-4" />
    <div class="no-print mb-6 flex flex-wrap items-center justify-end gap-3">
        <a href="{{ route('events.show', $event) }}" class="btn-secondary">戻る</a>
        <button type="button" onclick="window.print()" class="btn-primary"><x-icon name="printer" />印刷する</button>
    </div>

    <div class="no-print card mb-6 space-y-2 text-sm">
        <p class="font-bold">会場の回線やサーバーの不具合で全員がつながらなくなっても、このリストがあれば紙でイベントを続けられます。</p>
        <p>前日までに印刷して、受付に置いておいてください。当たりとハズレが書いてあるので、参加者には見せないでください。景品を足したときは、印刷し直してください。</p>
    </div>

    <article class="card print:rounded-none print:border-0 print:p-0 print:shadow-none">
        <header class="flex flex-wrap items-end justify-between gap-2 border-b-2 border-ink pb-2">
            <div>
                <p class="text-xs font-bold text-ink-soft">景品リスト（緊急時に紙で続けるとき用）</p>
                <h1 class="text-xl font-black print:text-[16pt]">{{ $event->name }}</h1>
            </div>
            <p class="text-xs text-ink-soft">{{ now()->format('Y年n月j日 H:i') }} 時点 ・ 参加者には見せない</p>
        </header>

        <ol class="mt-3 grid gap-x-6 gap-y-1 text-xs sm:grid-cols-2 print:grid-cols-2 print:text-[8.5pt]">
            <li><span class="font-black">1.</span> つながらなくなったら、主催者のスマホでほかのサイトが開くか確かめる（5分まで）</li>
            <li><span class="font-black">2.</span> 直らなければ「見つけたQRカードを取って受付に持ってきてね」と全員に伝える</li>
            <li><span class="font-black">3.</span> カードのお宝Noをこの表で探し、当たりなら景品を渡す。カードは回収し「渡した人」に名前を書く</li>
            <li><span class="font-black">4.</span> 途中で直っても、紙のまま最後まで続ける</li>
        </ol>
        @if ($event->hasPrizeLimit())
            <p class="mt-2 text-xs font-bold print:text-[8.5pt]">1人が持ってこられる枚数：{{ $event->prizeLimitLabel() }}</p>
        @endif

        @if ($prizes->isEmpty())
            <p class="mt-6 text-center text-ink-soft">まだ景品がありません。</p>
        @else
            <table class="mt-4 w-full border-collapse text-left text-sm print:text-[9.5pt]">
                <thead>
                    <tr class="border-b-2 border-ink">
                        <th class="w-16 py-1.5 pr-2">お宝No</th>
                        <th class="py-1.5 pr-2">景品名</th>
                        <th class="w-16 py-1.5 pr-2">種類</th>
                        <th class="w-36 py-1.5 pr-2">アプリで獲得</th>
                        <th class="w-40 py-1.5">渡した人</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prizes as $prize)
                        <tr class="break-inside-avoid border-b border-ink/30">
                            <td class="py-2 pr-2 text-lg font-black print:text-[12pt]">{{ $prize->number }}</td>
                            <td class="py-2 pr-2 font-bold">{{ $prize->name }}</td>
                            <td class="py-2 pr-2">{{ $prize->is_miss ? 'ハズレ' : '当たり' }}</td>
                            <td class="py-2 pr-2 text-xs">
                                @if ($prize->participant)
                                    {{ $prize->participant->nickname }}{{ $prize->isRedeemed() ? '（引換済）' : '' }}
                                @endif
                            </td>
                            <td class="py-2"><span class="block h-6 border-b border-dashed border-ink/50"></span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </article>
</x-layouts.app>
