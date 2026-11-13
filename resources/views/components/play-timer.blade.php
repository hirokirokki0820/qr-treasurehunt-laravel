@props(['event', 'compact' => false])

@php
    use App\Enums\TimerState;

    $state = $event->timerState();
    $status = $event->timerStatus();
    $remaining = $status['remaining'] ?? 0;
    $total = $event->started_at && $event->ends_at ? max(1, $event->started_at->diffInSeconds($event->ends_at)) : 1;
    $urgent = $state === TimerState::Running && $remaining <= 300;
    $poll = 'data-timer data-state="'.$state->value.'" data-version="'.$status['version'].'" data-status-url="'.route('play.status').'"';
@endphp

{{--
    参加者のマイページに出す宝探しタイマー。5秒ごとにサーバーへ状態を問い合わせ、
    スタート・延長・締め切りがあれば自動で再読み込みする（ブラウザの更新は不要）。
--}}
@if ($state === TimerState::Waiting)
    <div class="mt-6 rounded-2xl border-2 border-dashed border-sea/60 bg-sea/5 px-5 py-5 text-center" {!! $poll !!}>
        <x-icon name="hourglass" class="mx-auto h-9 w-9 text-sea motion-safe:animate-pulse" />
        <p class="mt-2 text-lg font-black">まもなく宝探しスタート！</p>
        <p class="mt-1 text-sm text-ink-soft">制限時間は{{ $event->time_limit_minutes }}分です。主催者がスタートすると、この画面で自動的にカウントダウンが始まります。</p>
    </div>
    <button type="button" disabled class="btn mt-4 w-full bg-ink/10 py-4 text-xl text-ink-soft">スタートを待っています…</button>
@elseif ($state === TimerState::Running && $compact)
    {{-- QR読み取り画面用の小さめの表示 --}}
    <div class="group mt-3 flex items-center gap-3 rounded-xl border-2 border-ink bg-white px-3 py-2 shadow-[3px_3px_0_0_var(--color-ink)] data-[urgent=true]:border-ruby data-[urgent=true]:bg-ruby/5 data-[urgent=true]:shadow-[3px_3px_0_0_var(--color-ruby)]" {!! $poll !!} data-remaining="{{ $remaining }}" data-total="{{ $total }}" data-urgent-at="300" data-urgent="{{ $urgent ? 'true' : 'false' }}">
        <x-icon name="timer" class="h-5 w-5 shrink-0 text-ink-soft group-data-[urgent=true]:text-ruby" />
        <div class="min-w-0 flex-1">
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-xs font-bold text-ink-soft group-data-[urgent=true]:text-ruby"><span class="group-data-[urgent=true]:hidden">残り時間</span><span class="hidden group-data-[urgent=true]:inline">あと少し！急げ！</span><span class="ml-1.5 font-normal text-ink-soft"><span data-timer-ends>{{ $event->ends_at->format('H:i') }}</span> まで</span></p>
                <p class="text-2xl leading-none font-black tabular-nums group-data-[urgent=true]:text-ruby group-data-[urgent=true]:motion-safe:animate-pulse" data-timer-clock>{{ sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) }}</p>
            </div>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-ink/10"><div class="h-full rounded-full bg-gold transition-[width] duration-1000 ease-linear group-data-[urgent=true]:bg-ruby" style="width: {{ round($remaining / $total * 100, 1) }}%" data-timer-bar></div></div>
        </div>
    </div>
@elseif ($state === TimerState::Running)
    <div class="group mt-6 rounded-2xl border-2 border-ink bg-white px-5 py-4 shadow-[4px_4px_0_0_var(--color-ink)] data-[urgent=true]:border-ruby data-[urgent=true]:bg-ruby/5 data-[urgent=true]:shadow-[4px_4px_0_0_var(--color-ruby)]" {!! $poll !!} data-remaining="{{ $remaining }}" data-total="{{ $total }}" data-urgent-at="300" data-urgent="{{ $urgent ? 'true' : 'false' }}">
        <div class="flex items-end justify-between">
            <p class="text-sm font-bold text-ink-soft group-data-[urgent=true]:text-ruby"><span class="group-data-[urgent=true]:hidden">残り時間</span><span class="hidden group-data-[urgent=true]:inline">残り時間・あと少し！急げ！</span></p>
            <p class="text-xs text-ink-soft"><span data-timer-ends>{{ $event->ends_at->format('H:i') }}</span> まで</p>
        </div>
        <p class="text-center text-5xl font-black tabular-nums group-data-[urgent=true]:text-ruby group-data-[urgent=true]:motion-safe:animate-pulse" data-timer-clock>{{ sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) }}</p>
        <div class="mt-3 h-3 overflow-hidden rounded-full bg-ink/10"><div class="h-full rounded-full bg-gold transition-[width] duration-1000 ease-linear group-data-[urgent=true]:bg-ruby" style="width: {{ round($remaining / $total * 100, 1) }}%" data-timer-bar></div></div>
    </div>
@elseif ($state === TimerState::Ended)
    <div class="mt-6 rounded-2xl bg-ink px-5 py-5 text-center text-white" role="status" {!! $poll !!}>
        <p class="text-lg font-black">宝探しは終了しました！</p>
        <p class="mt-1 text-sm text-white/80">お疲れさまでした。これ以上QRコードは読み取れません。</p>
        <p class="mt-3 text-sm font-bold text-gold">獲得した景品は、受付で交換してね！</p>
    </div>
@endif
