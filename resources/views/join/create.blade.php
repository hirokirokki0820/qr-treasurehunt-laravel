<x-layouts.app :title="$event->name.'に参加'">
    <div class="card mx-auto max-w-md text-center">
        <x-chest class="mx-auto h-20 w-20" />
        <p class="mt-3 text-sm font-bold tracking-widest text-ink-soft">宝探しに参加</p>
        <h1 class="text-2xl font-black">{{ $event->name }}</h1>
        @if ($event->description)
            <p class="mt-2 whitespace-pre-line text-ink-soft">{{ $event->description }}</p>
        @endif
        @if ($event->hasPrizeLimit())
            <p class="mt-3 inline-block rounded-full bg-gold/25 px-3 py-1 text-sm font-bold">お宝は{{ $event->prizeLimitLabel() }}</p>
        @endif

        <form method="POST" action="{{ route('join.store', $event) }}" class="mt-6 space-y-4 text-left">
            @csrf
            <x-errors />
            <div>
                <label for="nickname" class="label">ニックネーム</label>
                <input id="nickname" name="nickname" type="text" value="{{ old('nickname') }}" class="field" required maxlength="30" autofocus placeholder="例：たろう">
            </div>
            <button type="submit" class="btn-primary w-full text-lg">参加してスタート！</button>
        </form>

        <p class="mt-5 rounded-xl bg-parchment px-3 py-2 text-left text-xs text-ink-soft">
            ゲスト参加のため、パスワードはありません。このブラウザで参加中の状態が保たれます。別のブラウザやシークレットモードでは続きができないので注意してください。
        </p>
    </div>
</x-layouts.app>
