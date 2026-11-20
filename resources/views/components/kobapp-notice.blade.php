{{-- kobapp.jp から出した障害・メンテナンスのお知らせ（主催者としてログインしているときだけ。文は {{ }} で出す） --}}
@if (auth()->check() && ($kobappNotice = \App\Support\KobappNotice::current()))
    <div @class([
        'no-print border-b-2 px-4 py-3 text-sm',
        'border-ruby bg-ruby/10' => $kobappNotice['level'] === 'warning',
        'border-ink/20 bg-gold/15' => $kobappNotice['level'] !== 'warning',
    ]) role="status" data-kobapp-notice="{{ $kobappNotice['level'] }}">
        <div class="mx-auto flex max-w-5xl gap-2">
            <span class="shrink-0 font-black">{{ $kobappNotice['level'] === 'warning' ? '障害・メンテナンス' : 'お知らせ' }}</span>
            <p class="min-w-0 whitespace-pre-wrap break-words">{{ $kobappNotice['message'] }}</p>
        </div>
    </div>
@endif
