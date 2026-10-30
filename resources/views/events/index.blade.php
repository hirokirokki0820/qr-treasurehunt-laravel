<x-layouts.app title="イベント一覧">
    <div class="group/select" data-selection-root>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-ink-soft">{{ auth()->user()->name }} さんのマイページ</p>
                <h1 class="text-2xl font-black">イベント一覧</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('events.create') }}" class="btn-primary">＋ イベントを作成</a>
                @if ($events->isNotEmpty())
                    <button type="button" class="btn-secondary gap-1.5 whitespace-nowrap group-data-selecting/select:hidden" data-selection-enter>
                        <x-icon name="select" />
                        イベントを選択する
                    </button>
                    <form id="bulk-delete-form" method="POST" action="{{ route('events.destroy-many') }}" class="hidden group-data-selecting/select:block" data-confirm="選択したイベントを削除します。景品と参加者の記録もすべて削除され、元に戻せません。よろしいですか？">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger gap-1.5 whitespace-nowrap" data-bulk-delete-button disabled>
                            <x-icon name="trash" />
                            <span>選択したイベントを削除<span data-bulk-delete-count></span></span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-6">
                <x-errors />
            </div>
        @endif

        @if ($events->isNotEmpty())
            <div class="mt-6 hidden items-center justify-between gap-3 rounded-xl bg-ruby/8 px-4 py-2.5 text-sm group-data-selecting/select:flex">
                <label class="inline-flex cursor-pointer items-center gap-2 font-bold text-ink">
                    <input type="checkbox" class="h-4 w-4 accent-ruby" data-bulk-select-all>
                    すべて選択
                </label>
                <div class="flex items-center gap-3">
                    <span class="hidden text-ink-soft sm:inline">削除するイベントを選んでください</span>
                    <button type="button" class="rounded-lg border-2 border-ink/20 bg-white px-3 py-1 font-bold text-ink-soft transition hover:border-ink hover:text-ink" data-selection-cancel>
                        キャンセル
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-4 space-y-4">
            @forelse ($events as $event)
                <div class="card flex items-stretch p-0 transition hover:-translate-y-0.5 group-data-selecting/select:cursor-pointer has-[:checked]:bg-ruby/5 has-[:checked]:ring-2 has-[:checked]:ring-ruby/40" data-selection-card>
                    <label class="hidden cursor-pointer items-center pl-5 group-data-selecting/select:flex">
                        <input type="checkbox" name="event_ids[]" value="{{ $event->id }}" form="bulk-delete-form" class="h-5 w-5 accent-ruby" data-bulk-select-item aria-label="「{{ $event->name }}」を選択">
                    </label>
                    <a href="{{ route('events.show', $event) }}" class="block flex-1 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-lg font-black">{{ $event->name }}</h2>
                            <span class="text-sm text-ink-soft">{{ $event->created_at->format('Y/m/d') }} 作成</span>
                        </div>
                        <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
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
            @empty
                <div class="card text-center">
                    <x-chest class="mx-auto h-16 w-16" />
                    <p class="mt-3 font-bold">まだイベントがありません。</p>
                    <p class="text-sm text-ink-soft">「イベントを作成」から宝探しを準備しましょう。</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
