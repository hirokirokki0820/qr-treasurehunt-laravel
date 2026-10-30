<x-layouts.app :title="'参加者一覧 - '.$event->name">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '参加者一覧']]" />

    <div class="card mt-3">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <h1 class="text-2xl font-black">参加者一覧</h1>
            <p class="text-sm text-ink-soft">{{ $participants->count() }} 人</p>
        </div>

        @if ($duplicatedNicknames !== [])
            <p class="mt-4 flex items-start gap-2 rounded-xl bg-ruby/10 px-3 py-2 text-sm font-bold text-ruby">
                <x-icon name="alert" class="mt-px h-5 w-5 shrink-0" />
                <span>同じニックネームの参加者がいます（{{ implode('、', $duplicatedNicknames) }}）。参加者IDで見分けてください。参加者IDは、参加者のマイページにも表示されています。</span>
            </p>
        @endif

        @if ($participants->isEmpty())
            <p class="mt-6 py-6 text-center text-ink-soft">まだ参加者はいません。</p>
        @else
            <div class="mt-4 max-h-[58rem] overflow-auto overscroll-contain" data-scroll-rows="20">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="sticky top-0 z-10 w-12 bg-[#fdfcf9] py-2 pr-3 shadow-[inset_0_-2px_0_0_var(--color-ink)]">No.</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 shadow-[inset_0_-2px_0_0_var(--color-ink)]">ニックネーム</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 shadow-[inset_0_-2px_0_0_var(--color-ink)]">参加者ID</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 text-center shadow-[inset_0_-2px_0_0_var(--color-ink)]">獲得景品数</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 text-center shadow-[inset_0_-2px_0_0_var(--color-ink)]">引き換え</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 text-right shadow-[inset_0_-2px_0_0_var(--color-ink)]">参加時刻</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 shadow-[inset_0_-2px_0_0_var(--color-ink)]"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            @php($isDuplicated = in_array($participant->nickname, $duplicatedNicknames, true))
                            <tr @class(['border-b border-ink/15', 'bg-ruby/5' => $isDuplicated])>
                                <td class="py-2 pr-3 font-black">{{ $loop->iteration }}</td>
                                <td class="py-2 pr-3 font-bold break-all">
                                    {{ $participant->nickname }}
                                    @if ($isDuplicated)
                                        <span class="ml-1 rounded-full bg-ruby px-2 py-0.5 text-xs font-bold whitespace-nowrap text-white">同名あり</span>
                                    @endif
                                </td>
                                <td @class(['py-2 pr-3 font-mono whitespace-nowrap', 'font-black text-ruby' => $isDuplicated, 'text-ink-soft' => ! $isDuplicated])>{{ $participant->displayId() }}</td>
                                <td class="py-2 pr-3 text-center font-black">{{ $participant->prizes_count }}<span class="text-xs font-normal"> 個</span></td>
                                <td class="py-2 pr-3 text-center">
                                    @if ($participant->prizes_count === 0)
                                        <span class="text-ink-soft">—</span>
                                    @elseif ($participant->unredeemed_prizes_count === 0)
                                        <span class="rounded-full bg-sea px-2 py-0.5 text-xs font-bold whitespace-nowrap text-white">✓ 済</span>
                                    @else
                                        <span class="rounded-full bg-gold/30 px-2 py-0.5 text-xs font-bold whitespace-nowrap">未 {{ $participant->unredeemed_prizes_count }}個</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-right text-ink-soft">{{ $participant->created_at?->format('H:i') }}</td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('events.participants.show', [$event, $participant]) }}" class="font-bold whitespace-nowrap text-sea underline">獲得景品</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
