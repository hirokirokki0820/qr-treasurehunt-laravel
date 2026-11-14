{{-- 当日いちばん使うボタン。ほかのボタンより大きく、色も変えて目立たせる（スマホでは画面の下にも固定で出す） --}}
<a href="{{ route('redemption.scan', ['event' => $event->id]) }}" class="mt-4 flex w-full items-center justify-center gap-3 rounded-2xl border-2 border-ink bg-ink px-5 py-5 text-white shadow-[5px_5px_0_0_var(--color-gold)] transition hover:bg-[#2b3a5e] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-sea active:translate-x-[2px] active:translate-y-[2px] active:shadow-none" data-redeem-button>
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gold text-ink"><x-icon name="camera" class="h-7 w-7" /></span>
    <span class="text-left">
        <span class="block text-xl font-black sm:text-2xl">引き換えQRを読み取る</span>
        <span class="block text-sm text-white/80">景品を渡すときに、参加者のスマホや参加者カードのQRを読み取ります</span>
    </span>
</a>
