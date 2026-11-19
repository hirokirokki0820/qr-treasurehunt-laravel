@props(['event'])

{{-- 主催者のイベントページに出す、無料で使える範囲の案内（モニターでない人） --}}
@php($owner = $event->user)
@if ($owner && ! $owner->isMonitorOrAdmin())
    <section class="mt-6 rounded-2xl border-2 border-ink bg-white px-5 py-4 shadow-[4px_4px_0_0_var(--color-ink)]" data-plan-limit>
        <p class="font-black">参加者{{ \App\Models\User::FREE_PARTICIPANT_LIMIT }}人・お宝QR{{ \App\Models\User::FREE_PRIZE_LIMIT }}枚まで、本番は何度でも無料で使えます</p>
        <p class="mt-1 text-sm text-ink-soft">本番の回数が無制限なのは、有料プランを始めるまでの期間限定です（始めたあとは年3回になる予定）。お宝QRは当たり・ハズレの合計です。{{ \App\Models\User::FREE_PARTICIPANT_LIMIT }}人を超えそうなときは、無料モニター（参加者{{ \App\Models\User::MONITOR_PARTICIPANT_LIMIT }}人まで）に申し込むか、<a href="{{ rtrim((string) config('services.kobapp.url'), '/') }}/contact" class="font-bold text-sea underline" target="_blank" rel="noopener">お問い合わせ</a>ください。</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
            <a href="{{ rtrim((string) config('services.kobapp.url'), '/') }}/monitor" class="btn-secondary text-sm" target="_blank" rel="noopener">モニターに申し込む<span class="sr-only">（新しいタブで開きます）</span></a>
            <p class="text-xs text-ink-soft">モニターに決まったら、いちどログアウトして「kobapp でログイン」で入り直してください。</p>
        </div>
    </section>
@endif
