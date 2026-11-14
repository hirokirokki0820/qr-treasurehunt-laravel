@props(['event'])

@php
    use App\Enums\TimerState;

    $state = $event->timerState();
    $remaining = $event->remainingSeconds();
    $total = $event->started_at && $event->ends_at ? max(1, $event->started_at->diffInSeconds($event->ends_at)) : 1;
    $clock = fn (int $seconds) => sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
@endphp

{{-- 主催者のイベントページに出す宝探しタイマー。残り時間は画面側で1秒ごとに減らし、0になったら再読み込みする --}}
@if ($state === TimerState::Waiting)
    <section class="card mt-6 flex flex-wrap items-center justify-between gap-4 border-sea py-4 shadow-[4px_4px_0_0_var(--color-sea)]">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sea/10 text-sea"><x-icon name="timer" class="h-6 w-6" /></span>
            <div>
                <p class="text-sm font-bold text-ink-soft">宝探しタイマー・制限時間 <span class="text-ink">{{ $event->time_limit_minutes }}分</span></p>
                <p class="text-lg font-black">まだスタートしていません</p>
                <p class="text-xs text-ink-soft">スタートすると、参加者の画面で自動的にカウントダウンが始まります</p>
            </div>
        </div>
        <form method="POST" action="{{ route('events.timer.start', $event) }}" class="max-sm:w-full" data-confirm="宝探しをスタートします（制限時間 {{ $event->time_limit_minutes }}分）。参加者の画面でカウントダウンが始まります。よろしいですか？">
            @csrf
            <button type="submit" class="btn-primary w-full gap-2 px-6 py-3 text-lg"><x-icon name="play" class="h-5 w-5" />宝探しスタート</button>
        </form>
    </section>
@elseif ($state === TimerState::Running)
    <section class="card mt-6 border-sea py-4 shadow-[4px_4px_0_0_var(--color-sea)]" data-timer data-state="running" data-remaining="{{ $remaining }}" data-total="{{ $total }}">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="inline-flex items-center gap-1.5 rounded-full bg-sea px-2.5 py-0.5 text-xs font-bold text-white"><span class="h-2 w-2 rounded-full bg-white motion-safe:animate-pulse"></span>宝探し中</p>
                <p class="mt-1 text-4xl font-black tabular-nums">残り <span data-timer-clock>{{ $clock($remaining) }}</span></p>
                <p class="text-sm text-ink-soft">{{ $event->started_at->format('H:i') }} スタート ・ {{ $event->ends_at->format('H:i') }} 終了予定</p>
            </div>
            <div class="flex flex-wrap gap-2 max-sm:w-full">
                <form method="POST" action="{{ route('events.timer.reset', $event) }}" class="flex-1" data-confirm="タイマーをスタート前に戻します。参加者の画面も「まもなく宝探しスタート！」に戻り、宝箱を開けられなくなります（獲得済みの景品はそのまま残ります）。よろしいですか？" data-timer-reset>
                    @csrf
                    <button type="submit" class="btn-secondary w-full gap-1.5 text-sm whitespace-nowrap"><x-icon name="reset" class="h-4 w-4" />タイマーをリセット</button>
                </form>
                <form method="POST" action="{{ route('events.timer.extend', $event) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="btn-secondary w-full text-sm whitespace-nowrap">＋{{ \App\Http\Controllers\EventTimerController::EXTEND_MINUTES }}分延長</button>
                </form>
                <form method="POST" action="{{ route('events.timer.stop', $event) }}" class="flex-1" data-confirm="宝探しを今すぐ締め切ります。参加者は宝箱を開けられなくなります。よろしいですか？">
                    @csrf
                    <button type="submit" class="btn-danger w-full text-sm whitespace-nowrap">今すぐ締め切る</button>
                </form>
            </div>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-ink/10"><div class="h-full rounded-full bg-sea transition-[width] duration-1000 ease-linear" style="width: {{ round($remaining / $total * 100, 1) }}%" data-timer-bar></div></div>
    </section>
@elseif ($state === TimerState::Ended)
    <section class="card mt-6 flex flex-wrap items-center justify-between gap-4 py-4">
        <div>
            <p class="inline-flex items-center gap-1.5 rounded-full bg-ink px-2.5 py-0.5 text-xs font-bold text-white">終了</p>
            <p class="mt-1 text-lg font-black">宝探しは終了しました</p>
            <p class="text-sm text-ink-soft">{{ $event->started_at?->format('H:i') }}〜{{ $event->ends_at->format('H:i') }} ・ ここからは受付で景品の引き換えです</p>
        </div>
        <div class="flex flex-wrap gap-2 max-sm:w-full">
            <form method="POST" action="{{ route('events.timer.reset', $event) }}" class="flex-1" data-confirm="タイマーをスタート前に戻します。参加者の画面も「まもなく宝探しスタート！」に戻り、宝箱を開けられなくなります（獲得済みの景品はそのまま残ります）。よろしいですか？" data-timer-reset>
                @csrf
                <button type="submit" class="btn-secondary w-full gap-1.5 text-sm whitespace-nowrap"><x-icon name="reset" class="h-4 w-4" />タイマーをリセット</button>
            </form>
            <form method="POST" action="{{ route('events.timer.start', $event) }}" class="flex-1" data-confirm="宝探しをもう一度スタートします（制限時間 {{ $event->time_limit_minutes }}分）。獲得済みの景品はそのまま残ります。よろしいですか？">
                @csrf
                <button type="submit" class="btn-secondary w-full gap-1.5 text-sm whitespace-nowrap"><x-icon name="play" class="h-4 w-4" />もう一度スタート</button>
            </form>
        </div>
    </section>
@endif
