<x-layouts.app title="イベント作成">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => 'イベントを作成']]" />

    @if ($pastEvents->isNotEmpty())
        <form method="GET" action="{{ route('events.create') }}" class="card mt-3">
            <h2 class="font-black">過去のイベントから作成</h2>
            <p class="mt-1 text-sm text-ink-soft">選んだイベントのイベント名・説明・景品を引き継いで作成できます。</p>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                <label for="copy_from" class="sr-only">コピー元のイベント</label>
                <select id="copy_from" name="copy_from" class="field sm:flex-1" onchange="this.form.submit()">
                    <option value="">使わない（白紙から作成）</option>
                    @foreach ($pastEvents as $pastEvent)
                        <option value="{{ $pastEvent->id }}" @selected($source?->is($pastEvent))>
                            {{ $pastEvent->name }}（{{ $pastEvent->created_at->format('Y/m/d') }}・景品{{ $pastEvent->prizes_count }}個）
                        </option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn-secondary">反映する</button></noscript>
            </div>
        </form>
    @endif

    <div class="card mt-6">
        <h1 class="text-2xl font-black">イベントを作成</h1>

        @if ($source)
            <p class="mt-2 rounded-xl bg-sea/10 px-3 py-2 text-sm font-bold text-sea">「{{ $source->name }}」の内容を反映しました。必要に応じて書き換えてください。</p>
        @endif

        <form method="POST" action="{{ route('events.store') }}" class="mt-6 space-y-4">
            @csrf
            @include('events.form')

            @if ($source)
                <input type="hidden" name="copy_from" value="{{ $source->id }}">
                <label class="flex items-start gap-3 rounded-xl border-2 border-ink/20 bg-parchment/60 p-3">
                    <input type="hidden" name="copy_prizes" value="0">
                    <input type="checkbox" name="copy_prizes" value="1" class="mt-1 h-4 w-4 accent-sea" @checked(old('copy_prizes', true)) @disabled($source->prizes_count === 0)>
                    <span>
                        <span class="block font-bold">景品も引き継ぐ（{{ $source->prizes_count }}個）</span>
                        <span class="block text-sm text-ink-soft">景品No.・景品名・メッセージ・ハズレ設定をコピーします。在庫はすべて「○」の状態で、QRコードは新しく発行されるため印刷し直してください。</span>
                    </span>
                </label>
            @endif

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">作成する</button>
                <a href="{{ route('events.index') }}" class="btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</x-layouts.app>
