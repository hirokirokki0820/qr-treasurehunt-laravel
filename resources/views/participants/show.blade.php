<x-layouts.app :title="$participant->nickname.' さんの獲得景品'">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '参加者一覧', 'url' => route('events.participants', $event)], ['label' => $participant->nickname.' さん']]" />

    <div class="card mt-3">
        <h1 class="text-2xl font-black break-all">{{ $participant->nickname }} <span class="text-base">さん</span></h1>
        <p class="mt-1 text-sm text-ink-soft">参加者ID：<span class="font-bold text-ink">{{ $participant->displayId() }}</span></p>
        @php($redeemedCount = $prizes->filter->isRedeemed()->count())
        @php($unredeemedCount = $prizes->count() - $redeemedCount)
        <dl class="mt-4 grid grid-cols-3 gap-2 text-sm sm:gap-3">
            <div class="rounded-xl bg-parchment/70 p-3">
                <dt class="text-xs whitespace-nowrap text-ink-soft sm:text-sm">獲得景品数</dt>
                <dd class="text-2xl font-black">{{ $prizes->count() }}<span class="text-sm"> 個</span></dd>
            </div>
            <div class="rounded-xl bg-parchment/70 p-3">
                <dt class="text-xs whitespace-nowrap text-ink-soft sm:text-sm">引き換え済み</dt>
                <dd class="text-2xl font-black">{{ $redeemedCount }}<span class="text-sm"> 個</span></dd>
            </div>
            <div class="rounded-xl bg-parchment/70 p-3">
                <dt class="text-xs whitespace-nowrap text-ink-soft sm:text-sm">参加時刻</dt>
                <dd class="text-2xl font-black">{{ $participant->created_at?->format('H:i') }}</dd>
            </div>
        </dl>

        <h2 class="mt-6 text-lg font-black">獲得した景品</h2>
        @if ($prizes->isEmpty())
            <p class="mt-3 rounded-xl bg-ink/5 py-6 text-center text-ink-soft">まだ景品を獲得していません。</p>
        @else
            @if ($unredeemedCount > 0)
                <form method="POST" action="{{ route('events.participants.redeem', [$event, $participant]) }}" class="mt-3" data-confirm="{{ $participant->nickname }} さん（{{ $participant->displayId() }}）の景品 {{ $unredeemedCount }}個 を引き換え済みにします。よろしいですか？">
                    @csrf
                    <button type="submit" class="btn-primary w-full gap-2 py-3"><span aria-hidden="true">✓</span>まとめて引き換える（{{ $unredeemedCount }}個）</button>
                </form>
            @else
                <p class="mt-3 rounded-xl bg-sea/10 px-3 py-3 text-center font-bold text-sea">✓ すべての景品を引き換え済みです</p>
            @endif

            <table class="mt-3 w-full text-left text-sm">
                <thead>
                    <tr class="shadow-[inset_0_-2px_0_0_var(--color-ink)]">
                        <th class="w-10 py-2 pr-2">No.</th>
                        <th class="py-2 pr-2">景品名</th>
                        <th class="py-2 pr-2 text-right">獲得</th>
                        <th class="py-2 text-right">引き換え</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prizes as $prize)
                        <tr @class(['border-b border-ink/15', 'bg-sea/5' => $prize->isRedeemed()])>
                            <td class="py-2 pr-2 font-black">{{ $prize->number }}</td>
                            <td class="py-2 pr-2 font-bold">{{ $prize->name }}</td>
                            <td class="py-2 pr-2 text-right text-ink-soft">{{ $prize->acquired_at?->format('H:i') }}</td>
                            <td class="py-2 text-right">
                                @if ($prize->isRedeemed())
                                    <div class="flex flex-col items-end gap-0.5">
                                        <span class="rounded-full bg-sea px-2 py-0.5 text-xs font-bold whitespace-nowrap text-white">✓ 済 {{ $prize->redeemed_at->format('H:i') }}</span>
                                        <form method="POST" action="{{ route('events.prizes.redeem.cancel', [$event, $prize]) }}" data-confirm="No.{{ $prize->number }}「{{ $prize->name }}」の引き換えを取り消します。よろしいですか？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-ink-soft underline">取り消す</button>
                                        </form>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('events.prizes.redeem', [$event, $prize]) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg border-2 border-ink bg-white px-2 py-1 text-xs font-bold whitespace-nowrap shadow-[2px_2px_0_0_var(--color-ink)] transition hover:bg-gold/25 active:translate-y-px active:shadow-none">引き換える</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($misses->isNotEmpty())
            <h2 class="mt-6 text-lg font-black">開けたハズレ</h2>
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($misses as $miss)
                    <li class="rounded-full bg-ink/10 px-3 py-1 text-sm font-bold">No.{{ $miss->number }} {{ $miss->name }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.app>
