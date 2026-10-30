<x-layouts.app title="主催者アカウント作成">
    <div class="card mx-auto max-w-md">
        <h1 class="text-2xl font-black">主催者アカウント作成</h1>
        <p class="mt-1 text-sm text-ink-soft">メールアドレスは不要です。アカウント名とパスワードだけで始められます。</p>
        <p class="mt-2 text-sm font-bold text-ruby">※ メールアドレスを登録しないため、アカウント名やパスワードを忘れると再発行できません。必ず控えておいてください。</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <x-errors />

            <div>
                <label for="name" class="label">アカウント名（半角英数字 3〜30文字）</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="field" required autofocus autocomplete="username" pattern="[A-Za-z0-9]+">
            </div>
            <div>
                <label for="password" class="label">パスワード（8文字以上）</label>
                <input id="password" name="password" type="password" class="field" required autocomplete="new-password">
            </div>
            <div>
                <label for="password_confirmation" class="label">パスワード（確認）</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="field" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-primary w-full">作成する</button>
        </form>

        <p class="mt-5 text-center text-sm">
            アカウントをお持ちの方は <a href="{{ route('login') }}" class="font-bold text-sea underline">ログイン</a>
        </p>
    </div>
</x-layouts.app>
