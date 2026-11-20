{{-- リセットと片付け。どれも当日にはふだん使わないので、いちばん下にたたんでおく。押すと何が消えて何が残るかを書く --}}
<details class="mt-8 rounded-2xl border-2 border-ruby/40 bg-white/60 [&_summary::-webkit-details-marker]:hidden" data-danger-zone>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sea">
        <span class="font-black text-ruby">リセット・片付け</span>
        <span class="shrink-0 rounded-full bg-ruby/10 px-3 py-1 text-xs font-bold text-ruby" aria-hidden="true">開く／閉じる</span>
    </summary>
    <div class="grid gap-4 border-t-2 border-dashed border-ruby/25 px-5 pt-4 pb-5 md:grid-cols-2">
        <section class="flex flex-col gap-3 rounded-xl bg-white p-4">
            <h3 class="font-black">景品の獲得記録をリセット</h3>
            <ul class="space-y-1 text-sm">
                <li><span class="font-bold text-ruby">消えるもの：</span>すべての景品の獲得者と引き換えの記録（景品は「未獲得」に戻ります）</li>
                <li><span class="font-bold text-sea">残るもの：</span>参加者（ログインしたまま）、タイマー、景品とQRコード</li>
            </ul>
            <p class="text-xs text-ink-soft">タイマーを戻すときは、上のタイマーの「タイマーをリセット」を使います（獲得記録は消えません）。</p>
            <form method="POST" action="{{ route('events.reset-acquisitions', $event) }}" class="mt-auto" data-confirm="すべての景品を「未獲得」に戻します。獲得者と引き換えの記録が消えます（参加者とタイマーはそのままです）。よろしいですか？" data-confirm-again="本当にリセットしてよろしいですか？&#10;獲得済み・引き換え済みの景品も、すべて未獲得に戻ります。">
                @csrf
                <button type="submit" class="btn-danger w-full py-3"><x-icon name="reset" />獲得記録をリセット</button>
            </form>
        </section>
        <section class="flex flex-col gap-3 rounded-xl bg-white p-4">
            <h3 class="font-black">イベントを片付ける（次の回の準備）</h3>
            <ul class="space-y-1 text-sm">
                <li><span class="font-bold text-ruby">消えるもの：</span>参加者（全員ログアウト）、獲得と引き換えの記録、タイマーの進み具合（リセット。制限時間の設定は残る）、お試しモード、景品リストを印刷した印</li>
                <li><span class="font-bold text-sea">残るもの：</span>イベントの設定、景品、QRコード（印刷したQRを次の回にそのまま使えます）</li>
            </ul>
            <p class="text-xs text-ink-soft">イベントそのものを消すときは、イベント一覧の「イベントを選択する」から削除します。</p>
            <form method="POST" action="{{ route('events.end', $event) }}" class="mt-auto" data-confirm="イベントを片付けます。参加者は全員ログアウトされ、獲得と引き換えの記録が消えて、タイマーはリセットされます（景品とQRコード、制限時間の設定は残ります）。元に戻せません。よろしいですか？" data-confirm-again="本当に片付けてよろしいですか？&#10;参加中の人は全員ログアウトされます。">
                @csrf
                <button type="submit" class="btn-danger w-full py-3"><x-icon name="stop" />イベントを片付ける</button>
            </form>
        </section>
    </div>
</details>
