@php
    $filterLinks = [
        null => ['label' => 'すべて', 'count' => $event->prizes_count],
        'available' => ['label' => '未獲得', 'count' => $event->remaining_prizes_count],
        'unredeemed' => ['label' => '引き換え待ち', 'count' => $event->unredeemed_prizes_count],
    ];
    $back = ['available' => 'remaining', 'unredeemed' => 'unredeemed'][$stock] ?? null;
    $badge = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold whitespace-nowrap';
    $statusClass = [
        '未獲得' => 'bg-sea/12 text-sea',
        '引き換え待ち' => 'bg-gold/45 text-ink ring-1 ring-gold-deep/50',
        '引き換え済み' => 'bg-ink/10 text-ink-soft',
        '対象外' => 'bg-transparent text-ink-soft',
    ];
@endphp

<section id="prize-list" class="card group/select mt-6 scroll-mt-4" data-selection-root>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-black">景品一覧</h2>
            <p class="text-xs text-ink-soft" data-list-time>{{ now()->format('H:i:s') }} 時点の状態</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 group-data-selecting/select:hidden">
            {{-- 更新しても、絞り込みと一覧の位置はそのまま（同じURLだと読み込み直さないので、時刻を付ける） --}}
            <a href="{{ route('events.show', array_filter([$event, 'stock' => $stock, 't' => now()->timestamp])) }}#prize-list" class="btn-secondary min-h-10 gap-1.5 px-3 py-1.5 text-sm" title="最新の獲得状況を表示します" data-refresh-list><x-icon name="refresh" class="h-4 w-4" />更新</a>
            @if (in_array($event->phase(), ['live', 'closed'], true) && $snapshotPrizes->isNotEmpty())
                {{-- 景品の獲得状況を1枚の画像にして保存する（つながらなくなったときや、データが消えたときの控え）。画像はスマホの中で作る --}}
                <span class="inline-flex flex-col items-end gap-0.5" data-snapshot data-snapshot-key="qr-snapshot-{{ $event->id }}">
                    <button type="button" class="btn-secondary min-h-10 gap-1.5 px-3 py-1.5 text-sm data-[due]:bg-gold/40 data-[due]:ring-2 data-[due]:ring-gold-deep" data-snapshot-button><x-icon name="camera" class="h-4 w-4" />控えを保存</button>
                    <span class="text-[11px] text-ink-soft" data-snapshot-last>まだ保存していません</span>
                </span>
                @php
                    $snapshotData = [
                        'event' => $event->name,
                        'phase' => $event->phase() === 'live' ? '開催中' : '締切後',
                        'prizes' => $snapshotPrizes->map(fn ($prize) => [
                            'no' => $prize->number,
                            'name' => $prize->name,
                            'kind' => $prize->is_miss ? 'ハズレ' : '当たり',
                            'status' => $prize->statusLabel(),
                            'winner' => $prize->participant?->nickname,
                            'winnerId' => $prize->participant?->displayId(),
                            'at' => $prize->acquired_at?->format('H:i'),
                        ])->values()->all(),
                    ];
                @endphp
                <script type="application/json" data-snapshot-data>@json($snapshotData)</script>
            @endif
            @if ($prizes->isNotEmpty())
                <button type="button" class="btn-secondary min-h-10 gap-1.5 px-3 py-1.5 text-sm" data-selection-enter><x-icon name="select" class="h-4 w-4" />選択・削除</button>
            @endif
        </div>
    </div>

    {{-- 絞り込み（一覧のすぐ上）。選んでいるものは塗りつぶしと「✓」で示す --}}
    <nav class="mt-4 flex flex-wrap gap-2 group-data-selecting/select:hidden" aria-label="景品の絞り込み">
        @foreach ($filterLinks as $key => $link)
            @php($current = ($stock ?? '') === ($key ?? ''))
            <a href="{{ route('events.show', $key ? [$event, 'stock' => $key] : $event) }}#prize-list"
               @class([
                   'inline-flex min-h-10 items-center gap-1 rounded-full border-2 px-3.5 py-1.5 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea',
                   'border-ink bg-ink text-white' => $current,
                   'border-ink/30 bg-white text-ink hover:border-ink' => ! $current,
               ])
               @if ($current) aria-current="true" @endif>
                @if ($current)<span aria-hidden="true">✓</span>@endif{{ $link['label'] }}<span class="tabular-nums {{ $current ? 'text-white/80' : 'text-ink-soft' }}">{{ $link['count'] }}</span>
            </a>
        @endforeach
    </nav>
    @if ($stock === 'available')
        <p class="mt-2 text-sm text-ink-soft">まだ獲得されていない景品だけを表示しています（ハズレは除く）</p>
    @elseif ($stock === 'unredeemed')
        <p class="mt-2 text-sm text-ink-soft">見つけた人がいて、まだ景品を渡していないものだけを表示しています</p>
    @endif

    {{-- 選択・削除モード（「選択・削除」を押したときだけ、チェックボックスと削除ボタンを出す） --}}
    {{-- スマホでは、下の方の景品を選んでも押せるよう、画面の下に固定する（引き換えの固定ボタンは選択中だけ隠す） --}}
    <div class="mt-4 hidden flex-wrap items-center justify-between gap-3 rounded-xl bg-ruby/8 px-3 py-2.5 text-sm group-data-selecting/select:flex max-sm:fixed max-sm:inset-x-0 max-sm:bottom-0 max-sm:z-40 max-sm:mt-0 max-sm:rounded-none max-sm:border-t-2 max-sm:border-ruby max-sm:bg-[#fbeeea] max-sm:px-4 max-sm:pt-2.5 max-sm:pb-[calc(0.625rem+env(safe-area-inset-bottom))] max-sm:shadow-[0_-4px_12px_rgba(31,42,68,0.12)]" data-selection-bar>
        <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 px-1 font-bold">
            <input type="checkbox" class="h-5 w-5 accent-ruby" data-bulk-select-all>
            すべて選択
        </label>
        <div class="flex flex-wrap items-center gap-2">
            <form id="bulk-delete-form" method="POST" action="{{ route('events.prizes.destroy-many', $event) }}" data-confirm="選択した景品を削除します。獲得の記録も消え、元に戻せません。よろしいですか？">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger min-h-10 gap-1.5 px-3 py-1.5 text-sm disabled:cursor-not-allowed" data-bulk-delete-button disabled>
                    <x-icon name="trash" class="h-4 w-4" />
                    <span>削除<span data-bulk-delete-count></span></span>
                </button>
            </form>
            <button type="button" class="btn-secondary min-h-10 px-3 py-1.5 text-sm" data-selection-cancel>やめる</button>
        </div>
    </div>

    @if ($prizes->isEmpty())
        <p class="mt-6 py-6 text-center text-ink-soft">
            @if ($stock === 'available')
                残っている景品はありません。
            @elseif ($stock === 'unredeemed')
                引き換え待ちの景品はありません。
            @else
                まだ景品がありません。「景品を追加」から登録しましょう。
            @endif
        </p>
    @else
        {{-- PCでは表、スマホではカード（横スクロールなし）。並びは resources/css/app.css の .prize-table --}}
        <table class="prize-table mt-3 w-full text-left text-sm">
            <thead>
                <tr class="border-b-2 border-ink">
                    <th class="hidden w-10 py-2 pr-2 group-data-selecting/select:table-cell"><span class="sr-only">選択</span></th>
                    <th class="w-14 py-2 pr-3">No.</th>
                    <th class="py-2 pr-3">景品名</th>
                    <th class="w-48 py-2 pr-3">種類・状態</th>
                    <th class="w-56 py-2 pr-3">獲得者</th>
                    <th class="w-20 py-2"><span class="sr-only">詳細</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prizes as $prize)
                    @php($status = $prize->statusLabel())
                    <tr class="prize-row border-b border-ink/15 group-data-selecting/select:cursor-pointer has-[:checked]:bg-ruby/5" data-selection-card data-prize-status="{{ $status }}">
                        <td class="prize-cell-chk hidden py-2 pr-2 group-data-selecting/select:table-cell">
                            <label class="-m-2 inline-flex cursor-pointer p-2.5">
                                <input type="checkbox" name="prize_ids[]" value="{{ $prize->id }}" form="bulk-delete-form" class="h-5 w-5 accent-ruby" data-bulk-select-item data-bulk-label="No.{{ $prize->number }} {{ $prize->name }}" aria-label="No.{{ $prize->number }}「{{ $prize->name }}」を選択">
                            </label>
                        </td>
                        <td class="prize-cell-num py-2 pr-3 font-black tabular-nums"><span class="sm:hidden">No.</span>{{ $prize->number }}</td>
                        <td class="prize-cell-name py-2 pr-3 font-bold [overflow-wrap:anywhere]">{{ $prize->name }}</td>
                        <td class="prize-cell-meta py-2 pr-3">
                            <span class="flex flex-wrap items-center gap-1.5">
                                @if ($prize->is_miss)
                                    <span class="{{ $badge }} bg-ink/10">ハズレ</span>
                                    <span class="{{ $badge }} {{ $statusClass[$status] }}" title="ハズレは在庫の対象外です">対象外</span>
                                @else
                                    <span class="{{ $badge }} bg-gold/30">当たり</span>
                                    <span class="{{ $badge }} {{ $statusClass[$status] }}">{{ $status }}</span>
                                @endif
                            </span>
                        </td>
                        <td class="prize-cell-who py-2 pr-3">
                            @if ($prize->is_miss)
                                <span class="text-sm text-ink-soft max-sm:hidden">—</span>
                            @else
                                <span class="text-xs font-bold text-ink-soft sm:hidden">獲得者：</span>
                            @endif
                            @if ($prize->is_miss)
                            @elseif ($prize->participant)
                                <a href="{{ route('events.participants.show', [$event, $prize->participant]) }}" class="font-bold text-sea underline decoration-sea/40 underline-offset-2 hover:decoration-sea group-data-selecting/select:pointer-events-none group-data-selecting/select:no-underline" title="{{ $prize->participant->nickname }} さんの獲得景品を見る">{{ $prize->participant->nickname }}</a>
                                @if (in_array($prize->participant->nickname, $duplicatedNicknames, true))
                                    <span class="rounded bg-ruby/10 px-1 text-xs font-bold text-ruby" title="同じニックネームの参加者がいます">{{ $prize->participant->displayId() }}</span>
                                @endif
                                <span class="text-xs text-ink-soft">{{ $prize->acquired_at?->format('H:i') }}</span>
                            @else
                                <span class="text-sm text-ink-soft">なし</span>
                            @endif
                        </td>
                        <td class="prize-cell-detail py-2 text-right">
                            <a href="{{ route('events.prizes.show', [$event, $prize, 'back' => $back]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border-2 border-ink/30 bg-white px-3 text-sm font-bold text-sea transition hover:border-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea group-data-selecting/select:invisible">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="hidden h-24 max-sm:group-data-selecting/select:block" aria-hidden="true"></div>
    @endif
</section>
