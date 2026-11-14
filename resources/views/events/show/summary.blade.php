{{-- 数字を見るだけの集計（押せない）。一覧の絞り込みは、景品一覧の上にある --}}
<dl class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4" data-summary>
    <div class="rounded-xl border-2 border-ink/15 bg-white/60 px-3 py-2">
        <dt class="text-xs font-bold text-ink-soft">当たりの景品</dt>
        <dd class="text-2xl font-black tabular-nums">{{ $event->winning_prizes_count }}<span class="text-sm"> 個</span></dd>
    </div>
    <div class="rounded-xl border-2 border-ink/15 bg-white/60 px-3 py-2">
        <dt class="text-xs font-bold text-ink-soft">まだ見つかっていない</dt>
        <dd class="text-2xl font-black tabular-nums">{{ $event->remaining_prizes_count }}<span class="text-sm"> 個</span></dd>
    </div>
    <div @class(['rounded-xl border-2 px-3 py-2', 'border-gold-deep/60 bg-gold/25' => $event->unredeemed_prizes_count > 0, 'border-ink/15 bg-white/60' => $event->unredeemed_prizes_count === 0])>
        <dt class="text-xs font-bold text-ink-soft">引き換え待ち</dt>
        <dd class="text-2xl font-black tabular-nums">{{ $event->unredeemed_prizes_count }}<span class="text-sm"> 個</span></dd>
    </div>
    <div class="rounded-xl border-2 border-ink/15 bg-white/60 px-3 py-2">
        <dt class="text-xs font-bold text-ink-soft">参加者</dt>
        <dd class="flex flex-wrap items-baseline justify-between gap-x-2">
            <span class="text-2xl font-black tabular-nums">{{ $event->participants_count }}<span class="text-sm"> 人</span></span>
            @if ($event->participants_count > 0)
                <a href="{{ route('events.participants', $event) }}" class="text-sm font-bold text-sea underline underline-offset-2">一覧を見る</a>
            @endif
        </dd>
    </div>
</dl>
