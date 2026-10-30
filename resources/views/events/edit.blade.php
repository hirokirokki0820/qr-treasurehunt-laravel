<x-layouts.app title="イベント編集">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '編集']]" />
    <div class="card mt-3">
        <h1 class="text-2xl font-black">イベントを編集</h1>
        <form method="POST" action="{{ route('events.update', $event) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            @include('events.form')
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">更新する</button>
                <a href="{{ route('events.show', $event) }}" class="btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>

    <div class="card mt-8 border-ruby shadow-[4px_4px_0_0_var(--color-ruby)]">
        <h2 class="font-black text-ruby">イベントを削除</h2>
        <p class="mt-1 text-sm text-ink-soft">景品と参加者の記録もすべて削除されます。元に戻せません。</p>
        <form method="POST" action="{{ route('events.destroy', $event) }}" class="mt-4" data-confirm="「{{ $event->name }}」を削除します。よろしいですか？" data-confirm-again="本当に削除してよろしいですか？&#10;景品と参加者の記録もすべて消え、元に戻せません。">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">削除する</button>
        </form>
    </div>
</x-layouts.app>
