<x-layouts.app :title="$event->name" :wide="true">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name]]" />

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black">{{ $event->name }}</h1>
            @if ($event->description)
                <p class="mt-1 whitespace-pre-line text-ink-soft">{{ $event->description }}</p>
            @endif
            <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-ink/8 px-3 py-1 text-xs font-bold text-ink-soft">
                獲得上限：{{ $event->prizeLimitLabel() ?? 'なし' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('events.edit', $event) }}" class="btn-secondary text-sm">イベントを編集</a>
            <form method="POST" action="{{ route('events.end', $event) }}" data-confirm="イベントを終了します。参加者は全員ログアウトされ、景品の獲得状況もリセットされます（元に戻せません）。よろしいですか？" data-confirm-again="本当にイベントを終了してよろしいですか？&#10;参加中の人は全員ログアウトされます。">
                @csrf
                <button type="submit" class="btn-danger gap-1.5 text-sm"><x-icon name="stop" class="h-4 w-4 shrink-0" />イベントを終了</button>
            </form>
        </div>
    </div>

    @php($showingRemaining = ($filters['stock'] ?? null) === 'available')

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <x-stat-button
            :href="route('events.show', $event).'#prize-list'"
            label="景品数（ハズレを除く）"
            :value="$event->winning_prizes_count"
            unit="個"
            action="すべて表示"
            :active="! $showingRemaining"
        />
        <x-stat-button
            :href="route('events.show', [$event, 'stock' => 'available']).'#prize-list'"
            label="残り"
            :value="$event->remaining_prizes_count"
            unit="個"
            action="残りだけ表示"
            :active="$showingRemaining"
        />
        @if ($event->participants_count > 0)
            <x-stat-button
                :href="route('events.participants', $event)"
                label="参加者"
                :value="$event->participants_count"
                unit="人"
                action="一覧を見る"
            />
        @else
            <div class="card py-4">
                <p class="text-sm text-ink-soft">参加者</p>
                <p class="text-3xl font-black">0<span class="text-base"> 人</span></p>
            </div>
        @endif
    </div>

    <div class="mt-6">
        <x-errors />
    </div>

    <a href="{{ route('redemption.scan') }}" class="btn-primary mb-3 w-full gap-2 py-3"><x-icon name="camera" class="h-5 w-5 shrink-0" />引き換えQRを読み取る</a>

    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('events.join-qrcode', $event) }}" class="btn-secondary w-full gap-1 px-1.5 text-xs whitespace-nowrap max-[360px]:whitespace-normal sm:gap-2 sm:px-4 sm:text-base"><x-icon name="printer" class="h-4 w-4 shrink-0 sm:h-5 sm:w-5" />参加用QRコードを印刷</a>
        <a href="{{ route('events.qrcodes', $event) }}" class="btn-secondary w-full gap-1 px-1.5 text-xs whitespace-nowrap max-[360px]:whitespace-normal sm:gap-2 sm:px-4 sm:text-base"><x-icon name="printer" class="h-4 w-4 shrink-0 sm:h-5 sm:w-5" />お宝用QRコードを印刷</a>
    </div>

    <section id="prize-list" class="card mt-8 scroll-mt-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-black">景品一覧</h2>
            <a href="{{ request()->fullUrl() }}" class="btn-secondary w-40 gap-1.5 py-1.5 text-sm" title="最新の獲得状況を表示します">
                <x-icon name="refresh" class="h-4 w-4 shrink-0" />
                ページを更新
            </a>
        </div>

        @if ($showingRemaining)
            <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl bg-gold/20 px-3 py-2 text-sm font-bold">
                まだ獲得されていない景品だけを表示しています
                <a href="{{ route('events.show', $event) }}#prize-list" class="text-sea underline">すべて表示に戻す</a>
            </p>
        @endif

        <div class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,8fr)_minmax(15rem,2fr)]">
            <a
                href="{{ route('events.prizes.create', $event) }}"
                class="group flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-ink/50 bg-gold/15 px-4 py-3 font-black text-ink transition hover:border-solid hover:border-ink hover:bg-gold/35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea"
            >
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gold text-base leading-none text-ink ring-2 ring-ink transition group-hover:scale-110" aria-hidden="true">＋</span>
                景品を追加
            </a>
            <form id="bulk-delete-form" method="POST" action="{{ route('events.prizes.destroy-many', $event) }}" data-confirm="選択した景品を削除します。元に戻せません。よろしいですか？">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger h-full min-h-13 w-full gap-1.5 px-3 py-3 text-sm whitespace-nowrap" data-bulk-delete-button disabled>
                    <x-icon name="trash" />
                    <span>選択した景品を削除<span data-bulk-delete-count></span></span>
                </button>
            </form>
        </div>

        <div class="mt-4 max-h-[58rem] overflow-auto overscroll-contain" data-scroll-rows="20">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead>
                    <tr>
                        <th class="sticky top-0 z-10 bg-[#fdfcf9] shadow-[inset_0_-2px_0_0_var(--color-ink)] w-8 py-2 pr-3">
                            <input type="checkbox" class="h-4 w-4 accent-ruby" data-bulk-select-all aria-label="すべて選択" @disabled($prizes->isEmpty())>
                        </th>
                        <th class="sticky top-0 z-10 bg-[#fdfcf9] shadow-[inset_0_-2px_0_0_var(--color-ink)] py-2 pr-3">No.</th>
                        <th class="sticky top-0 z-10 bg-[#fdfcf9] shadow-[inset_0_-2px_0_0_var(--color-ink)] py-2 pr-3">景品名</th>
                        <th class="sticky top-0 z-10 bg-[#fdfcf9] shadow-[inset_0_-2px_0_0_var(--color-ink)] py-2 pr-3">種類</th>
                        <th class="sticky top-0 z-10 bg-[#fdfcf9] shadow-[inset_0_-2px_0_0_var(--color-ink)] py-2 pr-3 text-center">在庫</th>
                        <th class="sticky top-0 z-10 bg-[#fdfcf9] shadow-[inset_0_-2px_0_0_var(--color-ink)] py-2 pr-3">獲得者</th>
                        <th class="sticky top-0 z-10 bg-[#fdfcf9] shadow-[inset_0_-2px_0_0_var(--color-ink)] py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prizes as $prize)
                        <tr class="border-b border-ink/15 has-[:checked]:bg-ruby/5">
                            <td class="py-2 pr-3">
                                <input type="checkbox" name="prize_ids[]" value="{{ $prize->id }}" form="bulk-delete-form" class="h-4 w-4 accent-ruby" data-bulk-select-item aria-label="No.{{ $prize->number }} を選択">
                            </td>
                            <td class="py-2 pr-3 font-black">{{ $prize->number }}</td>
                            <td class="py-2 pr-3 font-bold">{{ $prize->name }}</td>
                            <td class="py-2 pr-3">
                                @if ($prize->is_miss)
                                    <span class="rounded-full bg-ink/10 px-2 py-0.5 text-xs font-bold">ハズレ</span>
                                @else
                                    <span class="rounded-full bg-gold/30 px-2 py-0.5 text-xs font-bold">当たり</span>
                                @endif
                            </td>
                            <td class="py-2 pr-3 text-center text-lg font-black">
                                @if ($prize->is_miss)
                                    <span class="text-sm font-normal text-ink-soft" title="ハズレは在庫の対象外です">—</span>
                                @elseif ($prize->isAvailable())
                                    <span class="text-sea" title="在庫あり">○</span>
                                @else
                                    <span class="text-ruby" title="獲得済み">×</span>
                                @endif
                            </td>
                            <td class="py-2 pr-3">
                                @if ($prize->participant)
                                    <a href="{{ route('events.participants.show', [$event, $prize->participant]) }}" class="font-bold text-sea underline decoration-sea/40 underline-offset-2 transition hover:decoration-sea" title="{{ $prize->participant->nickname }} さんの獲得景品を見る">{{ $prize->participant->nickname }}</a>
                                    @if (in_array($prize->participant->nickname, $duplicatedNicknames, true))
                                        <span class="rounded bg-ruby/10 px-1 text-xs font-bold text-ruby" title="同じニックネームの参加者がいます">{{ $prize->participant->displayId() }}</span>
                                    @endif
                                    <span class="text-xs text-ink-soft">{{ $prize->acquired_at?->format('H:i') }}</span>
                                    @if ($prize->isRedeemed())
                                        <span class="ml-1 rounded-full bg-sea/15 px-2 py-0.5 text-xs font-bold whitespace-nowrap text-sea">引換済</span>
                                    @else
                                        <span class="ml-1 rounded-full bg-gold/35 px-2 py-0.5 text-xs font-bold whitespace-nowrap text-ink" title="まだ景品を引き換えていません">未</span>
                                    @endif
                                @else
                                    <span class="text-sm font-normal text-ink-soft">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-right">
                                <a href="{{ route('events.prizes.show', [$event, $prize, 'back' => $showingRemaining ? 'remaining' : null]) }}" class="font-bold text-sea underline">詳細</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-ink-soft">
                                @if ($showingRemaining)
                                    残っている景品はありません。
                                @else
                                    まだ景品がありません。「景品を追加」から登録しましょう。
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <form method="POST" action="{{ route('events.reset-acquisitions', $event) }}" class="mt-6" data-confirm="すべての景品を未獲得（在庫○）に戻します。参加者の獲得記録も消えます。よろしいですか？" data-confirm-again="本当にリセットしてよろしいですか？&#10;獲得済みの景品はすべて未獲得に戻ります。">
        @csrf
        <button type="submit" class="btn-danger w-full py-3"><x-icon name="reset" />景品獲得状況（在庫）のリセット</button>
    </form>
</x-layouts.app>
