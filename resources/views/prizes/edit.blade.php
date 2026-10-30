<x-layouts.app title="景品の編集">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '景品 No.'.$prize->number, 'url' => route('events.prizes.show', [$event, $prize, 'back' => $back])], ['label' => '編集']]" />
    <div class="card mt-3">
        <h1 class="text-2xl font-black">景品 No.{{ $prize->number }} を編集</h1>
        <form method="POST" action="{{ route('events.prizes.update', [$event, $prize]) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            @if ($back)
                <input type="hidden" name="back" value="{{ $back }}">
            @endif
            @include('prizes.form')
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">更新する</button>
                <a href="{{ route('events.prizes.show', [$event, $prize, 'back' => $back]) }}" class="btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</x-layouts.app>
