<x-layouts.app title="最近削除したイベント">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => '最近削除したイベント']]" />

    <h1 class="text-2xl font-black">最近削除したイベント</h1>
    <p class="mt-3 rounded-xl bg-parchment-deep/60 px-4 py-3 text-sm text-ink-soft">削除してから{{ \App\Models\Event::TRASH_DAYS }}日間は、景品・参加者・獲得の記録ごと元に戻せます。そのあいだ、QRコードや参加者カードは使えません。{{ \App\Models\Event::TRASH_DAYS }}日たつと完全に削除されます。</p>

    @if ($trashed->isEmpty())
        <div class="card mt-6 text-center">
            <p class="font-bold">最近削除したイベントはありません。</p>
        </div>
    @else
        <ul class="card mt-6 divide-y divide-ink/10 py-1" data-trashed-events>
            @foreach ($trashed as $item)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <span class="min-w-0">
                        <span class="block font-bold [overflow-wrap:anywhere]">{{ $item->name }}</span>
                        <span class="block text-xs text-ink-soft">{{ $item->deleted_at->format('n月j日 H:i') }} に削除・{{ $item->purgeAt()->format('n月j日') }} に完全に削除</span>
                        <span class="block text-xs font-bold text-ruby">あと{{ max(1, (int) ceil(now()->diffInDays($item->purgeAt(), false))) }}日</span>
                    </span>
                    <form method="POST" action="{{ route('events.restore', $item->id) }}">
                        @csrf
                        <button type="submit" class="btn-secondary min-h-10 px-4 py-1.5 text-sm whitespace-nowrap" data-restore-button>元に戻す</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif

    <a href="{{ route('events.index') }}" class="btn-secondary mt-6 w-full">イベント一覧に戻る</a>
</x-layouts.app>
