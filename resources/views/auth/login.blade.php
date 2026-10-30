<x-layouts.app title="主催者ログイン">
    <div class="card mx-auto max-w-md">
        <h1 class="text-2xl font-black">主催者ログイン</h1>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <x-errors />

            <div>
                <label for="name" class="label">アカウント名</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="field" required autofocus autocomplete="username">
            </div>
            <div>
                <label for="password" class="label">パスワード</label>
                <input id="password" name="password" type="password" class="field" required autocomplete="current-password">
            </div>
            <label class="flex items-center gap-2 text-sm font-bold">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-sea">
                ログイン状態を保持する
            </label>

            <button type="submit" class="btn-primary w-full">ログイン</button>
        </form>

        <p class="mt-5 text-center text-sm">
            はじめての方は <a href="{{ route('register') }}" class="font-bold text-sea underline">アカウント作成</a>
        </p>
    </div>
</x-layouts.app>
