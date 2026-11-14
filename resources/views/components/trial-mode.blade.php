@props(['event'])

{{-- 主催者のイベントページに出す、お試しモードの案内（本番の前に、参加者3人までで流れを試す） --}}
@php($limit = \App\Models\Event::TRIAL_PARTICIPANT_LIMIT)
@if ($event->trial_mode)
    <section class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border-2 border-ink bg-gold/30 px-5 py-4 shadow-[4px_4px_0_0_var(--color-ink)]" data-trial-mode>
        <div>
            <p class="inline-flex rounded-full bg-ink px-2.5 py-0.5 text-xs font-bold text-white">お試しモード中</p>
            <p class="mt-1 text-lg font-black">参加者{{ $limit }}人まで（いま{{ $event->participants_count ?? $event->participants()->count() }}人）</p>
            <p class="text-sm text-ink-soft">参加用QRから参加して、会場の電波やスタート・景品の引き換えまでの流れを試してください。</p>
        </div>
        <form method="POST" action="{{ route('events.trial.end', $event) }}" class="max-sm:w-full" data-confirm="お試しを終えて本番にします。お試しの参加者と獲得の記録、タイマーを消します（景品とQRコードはそのまま使えます）。よろしいですか？">
            @csrf
            <button type="submit" class="btn-primary w-full gap-1.5 text-sm whitespace-nowrap"><x-icon name="play" class="h-4 w-4" />お試しを終えて本番にする</button>
        </form>
    </section>
@elseif ($event->canStartTrial())
    <section class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border-2 border-dashed border-ink/40 px-5 py-3">
        <div>
            <p class="font-black">本番の前に試してみる</p>
            <p class="text-sm text-ink-soft">お試しモード（参加者{{ $limit }}人まで）で、会場の電波や流れを確かめられます。終えると記録は消えるので、そのまま本番に使えます。</p>
        </div>
        <form method="POST" action="{{ route('events.trial.start', $event) }}" class="max-sm:w-full">
            @csrf
            <button type="submit" class="btn-secondary w-full text-sm whitespace-nowrap">お試しモードを始める</button>
        </form>
    </section>
@endif
