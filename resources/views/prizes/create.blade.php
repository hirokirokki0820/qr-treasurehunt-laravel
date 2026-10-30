<x-layouts.app title="景品の追加">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '景品を追加']]" />
    <div class="card mt-3">
        <h1 class="text-2xl font-black">景品を追加</h1>
        <p class="mt-1 text-sm text-ink-soft">景品No.は自動で付きます。<span class="hidden sm:inline">入力欄で Enter キーを押すと「登録して続けて追加」になります。</span></p>
        <form method="POST" action="{{ route('events.prizes.store', $event) }}" class="mt-6 space-y-4" data-enter-submits="[data-enter-submit]">
            @csrf
            @include('prizes.form')
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">登録する</button>
                <button type="submit" name="continue" value="1" data-enter-submit class="btn bg-[#eccb8e] text-ink shadow-[3px_3px_0_0_var(--color-ink)] hover:bg-[#e5bd74]">登録して続けて追加</button>
                <a href="{{ route('events.show', $event) }}" class="btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</x-layouts.app>
