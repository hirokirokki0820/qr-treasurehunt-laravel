<x-layouts.app :title="'景品 No.'.$prize->number">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => $backLink['url']], ['label' => '景品 No.'.$prize->number]]" />

    <div class="card mt-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-ink-soft">景品 No.{{ $prize->number }}</p>
                <h1 class="text-2xl font-black">{{ $prize->name }}</h1>
            </div>
            @if ($prize->is_miss)
                <span class="rounded-full bg-ink/10 px-3 py-1 text-sm font-bold">ハズレ</span>
            @else
                <span class="rounded-full bg-gold/30 px-3 py-1 text-sm font-bold">当たり</span>
            @endif
        </div>

        @if ($prize->description)
            <p class="mt-4 whitespace-pre-line">{{ $prize->description }}</p>
        @endif

        <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
            <div class="rounded-xl bg-parchment/70 p-3">
                <dt class="text-ink-soft">在庫</dt>
                <dd class="text-lg font-black">
                    @if ($prize->is_miss)
                        <span class="text-ink-soft">—</span>
                        <span class="text-sm font-normal text-ink-soft">ハズレは在庫の対象外です</span>
                    @else
                        {{ $prize->isAvailable() ? '○ あり' : '× なし' }}
                    @endif
                </dd>
            </div>
            <div class="rounded-xl bg-parchment/70 p-3">
                <dt class="text-ink-soft">獲得者</dt>
                <dd class="text-lg font-black">
                    @if ($prize->participant)
                        <a href="{{ route('events.participants.show', [$event, $prize->participant]) }}" class="text-sea underline decoration-sea/40 underline-offset-2 transition hover:decoration-sea" title="{{ $prize->participant->nickname }} さんの獲得景品を見る">{{ $prize->participant->nickname }}</a>
                        <span class="text-sm font-bold text-ink-soft">{{ $prize->participant->displayId() }}</span>
                    @else
                        —
                    @endif
                    @if ($prize->acquired_at)
                        <span class="text-sm font-normal text-ink-soft">{{ $prize->acquired_at->format('m/d H:i') }}</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-ink/40 p-4">
            <div class="aspect-square w-40" data-qr="{{ route('treasure.show', $prize) }}"></div>
            <p class="break-all text-xs text-ink-soft">{{ route('treasure.show', $prize) }}</p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('events.prizes.edit', [$event, $prize, 'back' => $backLink['param']]) }}" class="btn-primary">編集</a>
            <a href="{{ $backLink['url'] }}" class="btn-secondary">戻る</a>
            <form method="POST" action="{{ route('events.prizes.destroy', [$event, $prize]) }}" class="ml-auto" data-confirm="景品 No.{{ $prize->number }}「{{ $prize->name }}」を削除します。よろしいですか？">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">削除</button>
            </form>
        </div>
    </div>
</x-layouts.app>
