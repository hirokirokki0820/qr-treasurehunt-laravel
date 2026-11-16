{{-- イベント一覧の1件（index.blade.php から使う） --}}
<div id="event-{{ $event->id }}" class="card flex items-stretch p-0 transition hover:-translate-y-0.5 group-data-selecting/select:cursor-pointer has-[:checked]:bg-ruby/5 has-[:checked]:ring-2 has-[:checked]:ring-ruby/40" data-selection-card>
    <label class="hidden cursor-pointer items-center pl-5 group-data-selecting/select:flex">
        <input type="checkbox" name="event_ids[]" value="{{ $event->id }}" form="bulk-delete-form" class="h-5 w-5 accent-ruby" data-bulk-select-item data-bulk-label="{{ $event->name }}" aria-label="「{{ $event->name }}」を選択">
    </label>
    <a href="{{ route('events.show', $event) }}" class="block flex-1 p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-black">{{ $event->name }}</h2>
            <span class="text-sm text-ink-soft">{{ $event->created_at->format('Y/m/d') }} 作成</span>
        </div>
        <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
            <div class="basis-full"><dt class="inline text-ink-soft">開催予定</dt> <dd @class(['inline font-bold', 'text-ruby' => ! $event->hasSchedule() && $event->requires_schedule])>{{ $event->scheduleLabel() ?? ($event->requires_schedule ? '未入力' : '—') }}</dd></div>
            <div><dt class="inline text-ink-soft">景品数</dt> <dd class="inline font-bold">{{ $event->winning_prizes_count }}個</dd></div>
            <div><dt class="inline text-ink-soft">残り</dt> <dd class="inline font-bold">{{ $event->remaining_prizes_count }}個</dd></div>
            <div><dt class="inline text-ink-soft">参加者</dt> <dd class="inline font-bold">{{ $event->participants_count }}人</dd></div>
        </dl>
    </a>
    <div class="flex shrink-0 flex-col justify-center gap-2 border-l-2 border-dashed border-ink/15 p-3 group-data-selecting/select:hidden sm:px-4">
        <a href="{{ route('events.join-qrcode', $event) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border-2 border-ink bg-white px-2.5 py-1.5 text-xs font-bold whitespace-nowrap shadow-[2px_2px_0_0_var(--color-ink)] transition hover:-translate-y-px hover:bg-gold/25 active:translate-y-0 active:shadow-none" title="参加用QRコードを印刷">
            <x-icon name="qr-code" class="h-4 w-4 shrink-0" />参加用QR
        </a>
        <a href="{{ route('events.qrcodes', $event) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border-2 border-ink bg-white px-2.5 py-1.5 text-xs font-bold whitespace-nowrap shadow-[2px_2px_0_0_var(--color-ink)] transition hover:-translate-y-px hover:bg-gold/25 active:translate-y-0 active:shadow-none" title="お宝用QRコードを印刷">
            <x-icon name="qr-code" class="h-4 w-4 shrink-0" />お宝用QR
        </a>
    </div>
</div>
