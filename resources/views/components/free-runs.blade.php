@props(['user'])

{{-- 無料の主催者に出す、今年の本番の回数（年5回まで）。有料プランを始めるまでは、超えても止めずに案内だけ出す --}}
@if ($user && ! $user->isMonitorOrAdmin())
    @php($used = $user->runsThisYear())
    @php($limit = \App\Models\User::FREE_RUNS_PER_YEAR)
    @if ($used > $limit)
        <div {{ $attributes->merge(['class' => 'rounded-xl border-2 border-ink bg-gold/25 px-4 py-3 text-sm']) }} role="note" data-free-runs="over">
            <p class="font-black">今年の本番が{{ $used }}回になりました（無料は年{{ $limit }}回まで）</p>
            <p class="mt-0.5 text-ink-soft">今はこのまま使えます。定期的に開く場合は、<a href="{{ rtrim((string) config('services.kobapp.url'), '/') }}/contact" class="font-bold text-sea underline" target="_blank" rel="noopener">kobapp のお問い合わせ</a>からご相談ください。将来、{{ $limit + 1 }}回目以降は有料にする予定です。</p>
        </div>
    @else
        <p {{ $attributes->merge(['class' => 'text-sm text-ink-soft']) }} data-free-runs="{{ $used }}">今年の本番 <span class="font-black text-ink">{{ $used }}／{{ $limit }}回</span>（無料。お試しモードは数えません）</p>
    @endif
@endif
