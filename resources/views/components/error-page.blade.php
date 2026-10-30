@props(['code', 'title', 'sticker' => null, 'open' => false, 'dim' => false])

{{-- エラー画面の共通レイアウト。セッションやログイン状態に頼らない単独のページとして表示する --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ strip_tags($title) }} | QR宝探し</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen font-sans text-ink antialiased">
    <header class="border-b-2 border-ink bg-parchment-deep/70">
        <div class="mx-auto flex max-w-5xl items-center px-4 py-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-lg font-black tracking-wide">
                <x-chest class="h-8 w-8" />
                QR宝探し
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-md px-4 py-12">
        <div class="card text-center">
            <div class="relative mx-auto w-fit">
                <x-chest :open="$open" @class(['mx-auto h-28 w-28', 'opacity-50 grayscale' => $dim]) />
                @if ($sticker)
                    <span class="absolute -right-6 -bottom-1 rotate-6 rounded-md border-2 border-ink bg-gold px-2 py-0.5 text-xs font-black shadow-[2px_2px_0_0_var(--color-ink)]">{{ $sticker }}</span>
                @endif
            </div>
            <p class="mt-5 text-sm font-black tracking-widest text-gold-deep">ERROR {{ $code }}</p>
            <h1 class="mt-1 text-2xl font-black">{!! $title !!}</h1>
            <div class="mt-3 text-ink-soft">{{ $slot }}</div>

            <div class="mt-6 flex flex-col gap-3">
                {{ $actions ?? '' }}
            </div>
        </div>
    </main>
</body>
</html>
