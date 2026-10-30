<x-error-page code="500" title="エラーが<br>発生しました" sticker="故障中" :dim="true">
    サーバーで問題が発生しました。<br>時間をおいて、もう一度お試しください。
    <p class="mt-3 rounded-xl bg-gold/20 px-3 py-2 text-sm font-bold text-ink">宝探しに参加中の方も、<br>獲得した景品の記録は消えないので<br>安心してね！</p>

    <x-slot:actions>
        <button type="button" onclick="location.reload()" class="btn w-full bg-gold/35 text-ink shadow-[3px_3px_0_0_var(--color-ink)] hover:bg-gold/55"><x-icon name="refresh" class="h-5 w-5 shrink-0" />ページを再読み込み</button>
        <a href="{{ url('/') }}" class="btn-secondary w-full">トップページに戻る</a>
    </x-slot:actions>
</x-error-page>
