<x-layouts.app>
    <section class="py-6 text-center">
        <x-chest class="mx-auto h-28 w-28" :open="true" />
        <h1 class="mt-4 text-3xl font-black leading-tight sm:text-4xl">QRコードで<br class="sm:hidden">宝探しをしよう</h1>
        <p class="mx-auto mt-4 max-w-xl text-ink-soft">
            主催者は景品を登録してQRコードを印刷し、会場に隠すだけ。<br>
            参加者はスマホでQRコードを読み取って、早い者勝ちでお宝をゲット！
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth('web')
                <a href="{{ route('events.index') }}" class="btn-primary text-lg">主催者メニューへ</a>
            @else
                <a href="{{ route('register') }}" class="btn-primary text-lg">主催者として始める</a>
                <a href="{{ route('login') }}" class="btn-secondary text-lg">ログイン</a>
            @endauth

            @auth('participant')
                <a href="{{ route('play.index') }}" class="btn-secondary text-lg">参加中の宝探しへ</a>
            @endauth
        </div>
    </section>

    <section class="mt-10 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['1', '景品を登録', 'イベントを作り、景品やハズレを登録します。', 'register'],
            ['2', 'QRを隠す', 'お宝用QRコードを印刷して会場に隠し、参加用QRを配ります。', 'hide'],
            ['3', 'スマホで探す', '参加者はQRを読み取って宝箱を開け、獲得した景品を受付で見せます。', 'scan'],
        ] as [$step, $heading, $text, $illustration])
            <div class="card flex flex-col">
                <div class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-ink bg-gold font-black">{{ $step }}</div>
                <h2 class="mt-3 text-lg font-black">{{ $heading }}</h2>
                <p class="mt-1 text-sm text-ink-soft">{{ $text }}</p>
                <div class="mt-auto pt-5">
                    <div class="rounded-xl bg-parchment/70 px-3 py-3">
                        <x-step-illustration :name="$illustration" class="mx-auto h-auto w-full max-w-60" />
                    </div>
                </div>
            </div>
        @endforeach
    </section>
</x-layouts.app>
