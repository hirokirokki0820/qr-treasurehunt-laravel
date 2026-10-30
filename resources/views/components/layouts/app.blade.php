@props(['title' => null, 'wide' => false, 'forGuests' => false])

@php
    // 主催者用（/host 以下）と参加者用はログイン状態が別なので、今いる側のログインだけを見て表示を決める
    $isHostArea = \App\Http\Middleware\UseHostSession::isHostRequest(request());
    $organizer = $isHostArea ? auth('web')->user() : null;
    $participant = $isHostArea ? null : auth('participant')->user();
    $isGuestVisitor = ! $isHostArea && ($forGuests || \App\Http\Middleware\RememberGuestVisitor::isGuest(request()));

    // ロゴの行き先：主催者はイベント一覧、参加中のゲストはマイページ、ゲストはゲスト用トップ、それ以外はトップページ
    $logoUrl = match (true) {
        $organizer !== null => route('events.index'),
        $participant !== null => route('play.index'),
        $isGuestVisitor => route('guest.home'),
        default => route('home'),
    };
@endphp

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}QR宝探し</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-ink antialiased">
    <header class="no-print border-b-2 border-ink bg-parchment-deep/70">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ $logoUrl }}" class="flex items-center gap-2 text-lg font-black tracking-wide">
                <x-chest class="h-8 w-8" />
                QR宝探し
            </a>

            <nav class="flex flex-wrap items-center gap-2 text-sm">
                @if ($participant)
                    <a href="{{ route('play.index') }}" class="btn-secondary px-3 py-1.5">マイページ</a>
                    <a href="{{ route('play.scan') }}" class="btn-primary gap-1.5 px-3 py-1.5"><x-icon name="camera" class="h-4 w-4 shrink-0" />QRを読む</a>
                @endif

                @if ($organizer)
                    <a href="{{ route('events.index') }}" class="btn-secondary px-3 py-1.5">主催者メニュー</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-2 py-1.5 font-bold text-ink-soft underline-offset-4 hover:underline">ログアウト</button>
                    </form>
                @elseif (! $isHostArea && ! $isGuestVisitor && ! $participant)
                    <a href="{{ route('login') }}" class="rounded-lg px-2 py-1.5 font-bold text-ink-soft underline-offset-4 hover:underline">主催者ログイン</a>
                @endif
            </nav>
        </div>
    </header>

    @if (session('status'))
        <div
            class="no-print fixed inset-x-4 top-4 z-50 overflow-hidden rounded-2xl bg-ink text-white shadow-xl shadow-ink/30 ring-1 ring-white/10 transition duration-500 sm:left-auto sm:w-[28rem] starting:-translate-y-4 starting:opacity-0"
            role="status"
            data-auto-dismiss
        >
            <div class="flex items-start gap-3 px-4 py-3.5">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gold text-sm font-black text-ink" aria-hidden="true">✓</span>
                <p class="flex-1 pt-0.5 text-sm font-bold leading-relaxed">{{ session('status') }}</p>
                <button type="button" class="-mr-1 shrink-0 rounded-md px-1.5 text-xl leading-none text-white/60 transition hover:bg-white/10 hover:text-white" data-dismiss aria-label="閉じる">×</button>
            </div>
            <span class="block h-1 origin-left bg-gold motion-safe:animate-toast-timer" aria-hidden="true"></span>
        </div>
    @endif

    <main @class(['mx-auto px-4 py-8 print:max-w-none print:p-0', 'max-w-5xl' => $wide, 'max-w-3xl' => ! $wide])>
        {{ $slot }}
    </main>
</body>
</html>
