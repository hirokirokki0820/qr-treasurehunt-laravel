<x-error-page code="404" title="ページが<br>見つかりません" sticker="からっぽ" :open="true" :dim="true">
    お探しのページは、移動・削除されたか、<br>URLが間違っている可能性があります。
    <p class="mt-3 rounded-xl bg-gold/20 px-3 py-2 text-sm font-bold text-ink">QRコードから来た場合は、<br>もう一度読み取ってみてください。</p>

    <x-slot:actions>
        <a href="{{ url('/') }}" class="btn w-full bg-gold/35 text-ink shadow-[3px_3px_0_0_var(--color-ink)] hover:bg-gold/55">トップページに戻る</a>
        <button type="button" onclick="history.back()" class="btn-secondary w-full">前のページに戻る</button>
    </x-slot:actions>
</x-error-page>
