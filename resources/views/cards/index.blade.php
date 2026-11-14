<x-layouts.app :title="'参加者カード - '.$event->name">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name, 'url' => route('events.show', $event)], ['label' => '参加者カード']]" />

    <h1 class="mt-3 text-2xl font-black">参加者カード（スマホがない子用）</h1>
    <p class="mt-1 text-sm text-ink-soft">スマホを持っていない子（またはチーム）に、名札になるQRカードを1枚ずつ配ります。子どもは見つけたお宝QRを受付に持ってきて、主催者のスマホで「参加者カード → お宝QR」の順に読み取ります。当たりなら、その場で景品を渡せます。</p>

    <div class="mt-4">
        <x-errors />
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <a href="{{ route('cards.scan', ['event' => $event->id]) }}" class="btn-primary w-full gap-2 py-3"><x-icon name="camera" class="h-5 w-5 shrink-0" />参加者カードを読み取る</a>
        <a href="{{ route('cards.print', $event) }}" @class(['btn-secondary w-full gap-2 py-3', 'pointer-events-none opacity-50' => $cards->isEmpty()])><x-icon name="printer" class="h-5 w-5 shrink-0" />参加者カードを印刷</a>
    </div>

    <form method="POST" action="{{ route('cards.store', $event) }}" class="card mt-6 space-y-4">
        @csrf
        <h2 class="text-lg font-black">カードを作る</h2>
        <div>
            <label for="names" class="label">名前（1行に1人。チーム名でもOK）</label>
            <textarea id="names" name="names" class="field min-h-32" placeholder="例：&#10;たろう&#10;はなこ&#10;あおぐみ">{{ old('names') }}</textarea>
            <p class="mt-1 text-xs text-ink-soft">本名ではなく、ニックネームにしてください。</p>
        </div>
        <div>
            <label for="count" class="label">名前を決めずに作るときは、枚数（「カード1」「カード2」…になります）</label>
            <input id="count" name="count" type="number" min="1" max="{{ \App\Http\Controllers\ParticipantCardController::MAX_PER_REQUEST }}" value="{{ old('count') }}" class="field w-32">
        </div>
        @if ($event->trial_mode)
            <p class="rounded-xl bg-gold/25 px-3 py-2 text-sm font-bold">お試しモード中は、カードも含めて{{ \App\Models\Event::TRIAL_PARTICIPANT_LIMIT }}人までです。</p>
        @endif
        <button type="submit" class="btn-primary w-full">カードを作る</button>
    </form>

    <section class="card mt-6">
        <div class="flex items-baseline justify-between">
            <h2 class="text-lg font-black">作ったカード</h2>
            <p class="text-sm text-ink-soft">{{ $cards->count() }} 枚</p>
        </div>
        @if ($cards->isEmpty())
            <p class="mt-4 py-4 text-center text-ink-soft">まだカードはありません。</p>
        @else
            <ul class="mt-3 divide-y divide-ink/15 text-sm">
                @foreach ($cards as $card)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <span class="font-bold">{{ $card->nickname }} <span class="text-xs font-normal text-ink-soft">{{ $card->displayId() }}</span></span>
                        <span class="flex items-center gap-3">
                            <span class="text-ink-soft">獲得 {{ $card->prizes_count }}個</span>
                            <a href="{{ route('cards.play', $card->card_token) }}" class="font-bold text-sea underline">この子の番にする</a>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="mt-4 text-xs text-ink-soft">参加者カードの枚数も参加人数に数えます。イベントを終了すると、カードも使えなくなります（次の回は作り直して印刷してください）。</p>
    </section>
</x-layouts.app>
