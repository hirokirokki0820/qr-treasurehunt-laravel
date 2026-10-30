<x-error-page code="419" title="ページの<br>有効期限が切れました" sticker="時間切れ">
    しばらく操作がなかったため、<br>安全のために画面の期限が切れました。<br>前のページに戻って、<br>もう一度お試しください。

    <x-slot:actions>
        <button type="button" onclick="history.back()" class="btn w-full bg-gold/35 text-ink shadow-[3px_3px_0_0_var(--color-ink)] hover:bg-gold/55">前のページに戻る</button>
        <a href="{{ url('/') }}" class="btn-secondary w-full">トップページに戻る</a>
    </x-slot:actions>
</x-error-page>
