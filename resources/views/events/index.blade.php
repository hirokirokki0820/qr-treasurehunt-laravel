<x-layouts.app title="イベント一覧">
    <div class="group/select" data-selection-root>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-ink-soft">{{ auth()->user()->email }} さんのマイページ</p>
                <h1 class="text-2xl font-black">イベント一覧</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('events.create') }}" class="btn-primary">＋ イベントを作成</a>
                @if ($events->isNotEmpty())
                    <button type="button" class="btn-secondary gap-1.5 whitespace-nowrap group-data-selecting/select:hidden" data-selection-enter>
                        <x-icon name="select" />
                        イベントを選択する
                    </button>
                    <form id="bulk-delete-form" method="POST" action="{{ route('events.destroy-many') }}" class="hidden group-data-selecting/select:block" data-confirm="選択したイベントを削除します。削除したイベントのQRコードは使えなくなり、参加中の人もログアウトされます。30日以内なら「最近削除したイベント」から元に戻せます。よろしいですか？">
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
            @forelse ($foldAfter ? $events->take($foldAfter) : $events as $event)
                @include('events.index-card')
            @empty
                <div class="card text-center">
                    <x-chest class="mx-auto h-16 w-16" />
                    <p class="mt-3 font-bold">まだイベントがありません。</p>
                    <p class="text-sm text-ink-soft">「イベントを作成」から宝探しを準備しましょう。</p>
                </div>
            @endforelse

            @if ($foldAfter)
                {{-- 5〜10件：新しい4件のほかは折りたたむ --}}
                <details class="group/more space-y-4" data-more-events>
                    <summary class="flex cursor-pointer list-none items-center justify-center gap-2 rounded-xl border-2 border-dashed border-ink/30 bg-white/60 px-4 py-3 font-bold text-ink-soft hover:border-ink hover:text-ink [&::-webkit-details-marker]:hidden">
                        <span class="group-open/more:hidden">残り{{ $events->count() - $foldAfter }}件を表示 ▼</span>
                        <span class="hidden group-open/more:inline">閉じる ▲</span>
                    </summary>
                    @foreach ($events->slice($foldAfter) as $event)
                        @include('events.index-card')
                    @endforeach
                </details>
            @elseif ($events->count() < $total)
                {{-- 11件以上：5件ずつ足していく（足したところへ移る） --}}
                <a href="{{ route('events.index', ['show' => $events->count() + 5]) }}#event-{{ $events->last()->id }}" class="flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-ink/30 bg-white/60 px-4 py-3 font-bold text-ink-soft no-underline hover:border-ink hover:text-ink" data-show-more-events>
                    さらに{{ min(5, $total - $events->count()) }}件を表示（残り{{ $total - $events->count() }}件）▼
                </a>
            @endif
        </div>

        @if ($trashedCount > 0)
            <a href="{{ route('events.trash') }}" class="mt-8 flex items-center justify-center gap-1.5 text-sm font-bold text-sea underline underline-offset-2 group-data-selecting/select:hidden" data-trash-link>
                <x-icon name="trash" class="h-4 w-4" />最近削除したイベント（{{ $trashedCount }}件）›
            </a>
        @endif

    </div>
</x-layouts.app>
