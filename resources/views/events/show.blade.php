@php
    // 段階（準備中・お試し中・開催中・締切後）で、先に見せる操作を入れ替える。くわしくは Event::phase()
    $phase = $event->phase();
    $phaseLabels = ['prep' => '準備中', 'trial' => 'お試し中', 'live' => '開催中', 'closed' => '宝探しは締切・引き換え中'];
    $isOnTheDay = in_array($phase, ['live', 'closed'], true);
    $stock = $filters['stock'] ?? null;
@endphp

<x-layouts.app :title="$event->name" :wide="true">
    <x-breadcrumbs class="mb-4" :items="[['label' => 'イベント一覧', 'url' => route('events.index')], ['label' => $event->name]]" />

    <div class="mt-3">
        <p @class([
            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold',
            'bg-ink/10 text-ink' => $phase === 'prep',
            'bg-gold/40 text-ink' => $phase === 'trial',
            'bg-sea text-white' => $phase === 'live',
            'bg-ink text-white' => $phase === 'closed',
        ]) data-phase="{{ $phase }}">{{ $phaseLabels[$phase] }}</p>
        <h1 class="mt-2 text-2xl font-black break-words">{{ $event->name }}</h1>
        @if ($event->description)
            <p class="mt-1 whitespace-pre-line text-ink-soft">{{ $event->description }}</p>
        @endif
        @if ($event->hasSchedule())
            <p class="mt-2 inline-flex flex-wrap items-center gap-1.5 text-sm font-bold" data-schedule-label><x-icon name="timer" class="h-4 w-4" />開催予定：{{ $event->scheduleLabel() }}</p>
        @elseif ($event->requires_schedule)
            <p class="mt-2 inline-flex rounded-full bg-ruby/10 px-3 py-1 text-xs font-bold text-ruby" data-schedule-missing>開催予定：未入力</p>
        @endif
        <p class="mt-2 inline-flex flex-wrap items-center gap-1 rounded-full bg-ink/8 px-3 py-1 text-xs font-bold text-ink-soft">
            獲得上限：{{ $event->prizeLimitLabel() ?? 'なし' }} ・ 制限時間：{{ $event->hasTimeLimit() ? $event->time_limit_minutes.'分' : 'なし' }}
        </p>
    </div>

    <div class="mt-4">
        <x-errors />
    </div>

    @if ($isOnTheDay)
        {{-- 当日：残り時間 → 引き換え → 景品の状況 の順。準備の操作は下にたたむ --}}
        <x-event-timer :event="$event" />
        @include('events.show.redeem-button')
        @include('events.show.summary')
        @include('events.show.prizes')
        @include('events.show.setup', ['open' => false])
    @else
        {{-- 準備中・お試し中：景品の登録 → 印刷 → お試し → 本番のスタート の順 --}}
        @if ($event->needsScheduleToStart())
            {{-- 案B：作るときは空欄でもよいが、本番の前には開催予定が必要 --}}
            <section class="mt-6 rounded-2xl border-2 border-ink bg-gold/20 px-5 py-4" data-schedule-notice>
                <p class="font-black">本番の前に、開催の予定を入れてください</p>
                <p class="mt-1 text-sm text-ink-soft">予定を入れるまでは「宝探しスタート」を押せず、参加用QRからの参加も受け付けません。お試しモードは今のままで使えます。</p>
                <a href="{{ route('events.edit', $event) }}#schedule" class="btn-primary mt-3 text-sm">開催の予定を入れる</a>
            </section>
        @endif
        <x-plan-limit :event="$event" />
        @if ($phase === 'trial')
            <x-trial-mode :event="$event" />
            @include('events.show.redeem-button')
        @endif
        @include('events.show.setup', ['open' => true])
        @if ($phase === 'prep')
            <x-trial-mode :event="$event" />
        @endif
        <x-event-timer :event="$event" />
        @if (! $event->hasTimeLimit())
            <p class="mt-6 rounded-xl bg-white/70 px-4 py-3 text-sm text-ink-soft">制限時間なしのイベントです。参加用QRから参加した人から、すぐに宝探しを始められます。</p>
        @endif
        @include('events.show.summary')
        @include('events.show.prizes')
    @endif

    @include('events.show.danger-zone')

    @if ($isOnTheDay || $phase === 'trial')
        {{-- スマホでは、一覧を下までスクロールしても読み取りに進めるよう、画面の下に固定する。その分だけ下に余白を空ける --}}
        <div class="h-24 sm:hidden" aria-hidden="true"></div>
        <div class="no-print fixed inset-x-0 bottom-0 z-30 border-t-2 border-ink bg-parchment/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur sm:hidden" data-redeem-bar>
            <a href="{{ route('redemption.scan', ['event' => $event->id]) }}" class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-ink bg-ink px-4 py-3.5 text-lg font-black text-white shadow-[3px_3px_0_0_var(--color-gold)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none">
                <x-icon name="camera" class="h-6 w-6" />引き換えQRを読み取る
            </a>
        </div>
    @endif
</x-layouts.app>
