<x-error-page code="403" title="このページは<br>表示できません" sticker="カギ付き">
    このページを見る権限がありません。<br>ログイン中のアカウントを<br>確認してください。

    <x-slot:actions>
        <a href="{{ url('/') }}" class="btn w-full bg-gold/35 text-ink shadow-[3px_3px_0_0_var(--color-ink)] hover:bg-gold/55">トップページに戻る</a>
        <button type="button" onclick="history.back()" class="btn-secondary w-full">前のページに戻る</button>
    </x-slot:actions>
</x-error-page>
