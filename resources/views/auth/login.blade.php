<x-layouts.app title="主催者ログイン">
    <div class="card mx-auto max-w-md text-center">
        <h1 class="text-2xl font-black">主催者ログイン</h1>
        <p class="mt-3 text-ink-soft">QR宝探しの主催者は、kobapp アカウントでログインします。</p>

        @if (session('error'))
            <p class="mt-4 rounded-xl bg-ruby/10 px-3 py-2 text-left text-sm font-bold text-ruby" role="alert">{{ session('error') }}</p>
        @endif
        @if (session('status'))
            <p class="mt-4 rounded-xl bg-sea/10 px-3 py-2 text-left text-sm font-bold text-sea" role="status">{{ session('status') }}</p>
        @endif

        <a href="{{ route('login.kobapp') }}" class="btn-primary mt-6 w-full py-4 text-lg">kobapp でログイン</a>

        <p class="mt-5 text-sm">
            まだアカウントがない方は <a href="{{ rtrim(config('services.kobapp.url'), '/') }}/register" class="font-bold text-sea underline">kobapp で無料登録</a>
        </p>
        <p class="mt-3 text-xs text-ink-soft">参加者の方は、ログインはいりません。会場の参加用QRコードを読み取ってください。</p>
    </div>
</x-layouts.app>
