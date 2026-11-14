@props(['event'])

{{-- 主催者のイベントページに出す、参加できる人数の案内（モニターでない人は、正式なサービスを始めるまで参加者3人まで） --}}
@php($owner = $event->user)
@if ($owner && ! $owner->isMonitorOrAdmin())
    <section class="mt-6 rounded-2xl border-2 border-ink bg-white px-5 py-4 shadow-[4px_4px_0_0_var(--color-ink)]" data-plan-limit>
        <p class="font-black">いまは、参加者{{ \App\Models\User::FREE_PARTICIPANT_LIMIT }}人まで使えます</p>
        <p class="mt-1 text-sm text-ink-soft">正式なサービスを始めるまで、4人以上のイベントで使えるのは、モニターに決まった方だけです。モニターになると、参加者{{ \App\Models\User::MONITOR_PARTICIPANT_LIMIT }}人まで無料で使えます。</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
            <a href="{{ rtrim((string) config('services.kobapp.url'), '/') }}/monitor" class="btn-secondary text-sm" target="_blank" rel="noopener">モニターに申し込む<span class="sr-only">（新しいタブで開きます）</span></a>
            <p class="text-xs text-ink-soft">モニターに決まったら、いちどログアウトして「kobapp でログイン」で入り直してください。</p>
        </div>
    </section>
@endif
