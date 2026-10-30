{{-- メンテナンス中（php artisan down）に表示する画面。ログイン状態などに頼らない単独のページにしている --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>メンテナンス中 | QR宝探し</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen font-sans text-ink antialiased">
    <header class="border-b-2 border-ink bg-parchment-deep/70">
        <div class="mx-auto flex max-w-5xl items-center gap-2 px-4 py-3 text-lg font-black tracking-wide">
            <x-chest class="h-8 w-8" />
            QR宝探し
        </div>
    </header>

    <main class="mx-auto max-w-md px-4 py-12">
        <div class="card text-center">
            <div class="relative mx-auto w-fit">
                <x-chest class="mx-auto h-28 w-28" />
                <span class="absolute -right-6 -bottom-1 rotate-6 rounded-md border-2 border-ink bg-gold px-2 py-0.5 text-xs font-black shadow-[2px_2px_0_0_var(--color-ink)]">準備中</span>
            </div>
            <p class="mt-5 text-sm font-black tracking-widest text-gold-deep">MAINTENANCE</p>
            <h1 class="mt-1 text-2xl font-black">ただいま<br>メンテナンス中です</h1>
            <p class="mt-3 text-ink-soft">サイトの準備をしています。<br>しばらくしてから、<br>もう一度アクセスしてください。</p>

            <p class="mt-5 rounded-xl bg-gold/20 px-3 py-2 text-sm font-bold">宝探しに参加中の方も、<br>獲得した景品の記録は消えないので<br>安心してね！</p>

            <button type="button" onclick="location.reload()" class="btn mt-6 w-full bg-gold/35 text-ink shadow-[3px_3px_0_0_var(--color-ink)] hover:bg-gold/55"><x-icon name="refresh" class="h-5 w-5 shrink-0" />ページを再読み込み</button>
        </div>
    </main>
</body>
</html>
