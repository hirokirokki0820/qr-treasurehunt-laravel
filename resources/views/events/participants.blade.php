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
            {{-- スマホ：参加者ごとのカード --}}
            <ul class="mt-4 divide-y divide-ink/15 sm:hidden" data-participant-cards>
                @foreach ($participants as $participant)
                    @php($isDuplicated = in_array($participant->nickname, $duplicatedNicknames, true))
                    <li @class(['py-3', 'bg-ruby/5' => $isDuplicated])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('events.participants.show', [$event, $participant]) }}" class="font-black text-sea underline decoration-sea/40 underline-offset-2 [overflow-wrap:anywhere]">{{ $participant->nickname }}</a>
                                <span @class(['ml-1 font-mono text-xs whitespace-nowrap', 'font-black text-ruby' => $isDuplicated, 'text-ink-soft' => ! $isDuplicated])>{{ $participant->displayId() }}</span>
                                @if ($participant->isCard())
                                    <span class="rounded-full bg-sea/15 px-2 py-0.5 text-xs font-bold whitespace-nowrap text-sea">カード</span>
                                @endif
                                @if ($isDuplicated)
                                    <span class="rounded-full bg-ruby px-2 py-0.5 text-xs font-bold whitespace-nowrap text-white">同名あり</span>
                                @endif
                                <p class="mt-1 text-sm">
                                    獲得 <span class="font-black">{{ $participant->prizes_count }}個</span>
                                    @if ($participant->prizes_count > 0)
                                        ／
                                        @if ($participant->unredeemed_prizes_count === 0)
                                            <span class="font-bold text-sea">✓ すべて引き換え済み</span>
                                        @else
                                            <span class="rounded-full bg-gold/35 px-2 py-0.5 text-xs font-bold whitespace-nowrap">未引き換え {{ $participant->unredeemed_prizes_count }}個</span>
                                        @endif
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-ink-soft">参加時刻：{{ $participant->created_at?->format('H:i') }}</p>
                            </div>
                            <a href="{{ route('events.participants.show', [$event, $participant]) }}" class="inline-flex min-h-10 shrink-0 items-center rounded-lg border-2 border-ink/30 bg-white px-3 text-sm font-bold text-sea hover:border-ink">獲得景品を見る</a>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- PC：表 --}}
            <div class="mt-4 hidden max-h-[58rem] overflow-auto overscroll-contain sm:block" data-scroll-rows="20">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="sticky top-0 z-10 w-12 bg-[#fdfcf9] py-2 pr-3 shadow-[inset_0_-2px_0_0_var(--color-ink)]">No.</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 shadow-[inset_0_-2px_0_0_var(--color-ink)]">ニックネーム</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 whitespace-nowrap shadow-[inset_0_-2px_0_0_var(--color-ink)]">参加者ID</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 text-center whitespace-nowrap shadow-[inset_0_-2px_0_0_var(--color-ink)]">獲得景品数</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 text-center whitespace-nowrap shadow-[inset_0_-2px_0_0_var(--color-ink)]">引き換え</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 pr-3 text-right whitespace-nowrap shadow-[inset_0_-2px_0_0_var(--color-ink)]">参加時刻</th>
                            <th class="sticky top-0 z-10 bg-[#fdfcf9] py-2 shadow-[inset_0_-2px_0_0_var(--color-ink)]"><span class="sr-only">詳細</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            @php($isDuplicated = in_array($participant->nickname, $duplicatedNicknames, true))
                            <tr @class(['border-b border-ink/15', 'bg-ruby/5' => $isDuplicated])>
                                <td class="py-2 pr-3 font-black">{{ $loop->iteration }}</td>
                                <td class="py-2 pr-3 font-bold [overflow-wrap:anywhere]">
                                    <a href="{{ route('events.participants.show', [$event, $participant]) }}" class="text-sea underline decoration-sea/40 underline-offset-2 hover:decoration-sea">{{ $participant->nickname }}</a>
                                    @if ($participant->isCard())
                                        <span class="rounded-full bg-sea/15 px-2 py-0.5 text-xs font-bold whitespace-nowrap text-sea">カード</span>
                                    @endif
                                    @if ($isDuplicated)
                                        <span class="ml-1 rounded-full bg-ruby px-2 py-0.5 text-xs font-bold whitespace-nowrap text-white">同名あり</span>
                                    @endif
                                </td>
                                <td @class(['py-2 pr-3 font-mono whitespace-nowrap', 'font-black text-ruby' => $isDuplicated, 'text-ink-soft' => ! $isDuplicated])>{{ $participant->displayId() }}</td>
                                <td class="py-2 pr-3 text-center font-black whitespace-nowrap">{{ $participant->prizes_count }}<span class="text-xs font-normal"> 個</span></td>
                                <td class="py-2 pr-3 text-center">
                                    @if ($participant->prizes_count === 0)
                                        <span class="text-ink-soft">—</span>
                                    @elseif ($participant->unredeemed_prizes_count === 0)
                                        <span class="rounded-full bg-sea px-2 py-0.5 text-xs font-bold whitespace-nowrap text-white">✓ 引き換え済み</span>
                                    @else
                                        <span class="rounded-full bg-gold/30 px-2 py-0.5 text-xs font-bold whitespace-nowrap">未引き換え {{ $participant->unredeemed_prizes_count }}個</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-right whitespace-nowrap text-ink-soft">{{ $participant->created_at?->format('H:i') }}</td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('events.participants.show', [$event, $participant]) }}" class="inline-flex min-h-9 items-center rounded-lg border-2 border-ink/30 bg-white px-3 text-sm font-bold whitespace-nowrap text-sea hover:border-ink">獲得景品を見る</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
