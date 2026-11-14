@props(['event'])

{{-- 「タイマーをリセット」。延長や締切と押し間違えないよう、たたんだ中に置く。景品の獲得記録のリセットとは別のもの --}}
<details class="mt-3 text-sm [&_summary::-webkit-details-marker]:hidden">
    <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-1 rounded-lg px-1 font-bold text-ink-soft underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea">タイマーのその他の操作</summary>
    <div class="mt-2 flex flex-wrap items-center gap-3 rounded-xl bg-ink/5 p-3">
        <p class="min-w-0 flex-[1_1_16rem] text-xs text-ink-soft">タイマーをスタート前に戻します。参加者は宝箱を開けられなくなります。獲得の記録は消えません（消すときは、下の「リセット・片付け」から）。</p>
        <form method="POST" action="{{ route('events.timer.reset', $event) }}" data-confirm="タイマーをスタート前に戻します。参加者の画面も「まもなく宝探しスタート！」に戻り、宝箱を開けられなくなります（獲得済みの景品はそのまま残ります）。よろしいですか？" data-timer-reset>
            @csrf
            <button type="submit" class="btn-secondary min-h-10 gap-1.5 text-sm whitespace-nowrap"><x-icon name="reset" class="h-4 w-4" />タイマーをリセット</button>
        </form>
    </div>
</details>
