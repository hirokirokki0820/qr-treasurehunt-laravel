@php
    $needsPrizeListPrint = $event->needsPrizeListPrint();
    $item = 'flex min-h-12 w-full items-center gap-2 rounded-xl border-2 border-ink bg-white px-3 py-2.5 text-left text-sm font-bold shadow-[2px_2px_0_0_var(--color-ink)] transition hover:bg-gold/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea active:translate-x-px active:translate-y-px active:shadow-none';
@endphp

{{-- 準備・設定（印刷・景品の追加・編集など）。準備中は開いて、当日はたたんで下に置く --}}
<details class="card mt-6 p-0 [&_summary::-webkit-details-marker]:hidden" @if ($open) open @endif data-setup data-setup-open="{{ $open ? 'yes' : 'no' }}">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-2xl px-5 py-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea">
        <span>
            <span class="block text-lg font-black">{{ $open ? '準備' : '準備・設定' }}</span>
            <span class="block text-xs text-ink-soft">景品の追加、QRコードや景品リストの印刷、参加者カード、イベントの編集</span>
        </span>
        <span class="shrink-0 rounded-full bg-ink/10 px-3 py-1 text-xs font-bold" aria-hidden="true">開く／閉じる</span>
    </summary>

    <div class="border-t-2 border-dashed border-ink/15 px-5 pt-4 pb-5">
        @if ($needsPrizeListPrint)
            <div class="mb-4 rounded-xl border-2 border-ink bg-gold/25 px-4 py-3 text-sm" role="note" data-prize-list-reminder>
                <p class="font-black">{{ $event->prize_list_printed_at ? '景品を足したので、景品リストを印刷し直しましょう' : '当日の備えに、景品リストを印刷しておきましょう' }}</p>
                <p class="mt-0.5 text-ink-soft">会場の回線やサーバーの不具合で全員がつながらなくなっても、紙のリストがあればイベントを続けられます。<a href="{{ rtrim(config('services.kobapp.url'), '/') }}/help/qr-takarasagashi/trouble#emergency" class="font-bold text-sea underline" target="_blank" rel="noopener">紙で続ける方法</a></p>
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('events.prizes.create', $event) }}" class="{{ $item }} border-dashed bg-gold/15">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gold text-base leading-none ring-2 ring-ink" aria-hidden="true">＋</span>景品を追加
            </a>
            <a href="{{ route('events.join-qrcode', $event) }}" class="{{ $item }}"><x-icon name="printer" class="h-5 w-5" />参加用QRを印刷</a>
            <a href="{{ route('events.qrcodes', $event) }}" class="{{ $item }}"><x-icon name="printer" class="h-5 w-5" />お宝用QRを印刷</a>
            <a href="{{ route('events.prize-list', $event) }}" @class([$item, 'bg-gold/30' => $needsPrizeListPrint]) data-prize-list-print-button>
                <x-icon name="printer" class="h-5 w-5" />
                <span>景品リストを印刷<span class="block text-xs font-normal text-ink-soft">緊急時に紙で続ける用</span></span>
            </a>
            <a href="{{ route('cards.index', $event) }}" class="{{ $item }}">
                <x-icon name="qr-code" class="h-5 w-5" />
                <span>参加者カード<span class="block text-xs font-normal text-ink-soft">スマホがない子用</span></span>
            </a>
            <a href="{{ route('events.edit', $event) }}" class="{{ $item }}">
                <x-icon name="select" class="h-5 w-5" />
                <span>イベントを編集<span class="block text-xs font-normal text-ink-soft">名前・制限時間・獲得上限など</span></span>
            </a>
        </div>

        @if (in_array($event->phase(), ['live', 'closed'], true) && $event->isTimerUsed() && ($event->participants_count ?? 0) === 0)
            <p class="mt-4 text-xs text-ink-soft" data-trial-blocked-by-timer>お試しモードは、タイマーをスタートしたあとは始められません。試すときは、先にタイマーをリセットしてください。</p>
        @endif
    </div>
</details>
