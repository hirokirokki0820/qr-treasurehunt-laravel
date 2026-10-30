<x-error-page code="429" title="アクセスが<br>集中しています" sticker="ちょっと待って">
    短い時間に<br>たくさんのアクセスがありました。<br>少し時間をおいてから、<br>もう一度お試しください。

    <x-slot:actions>
        <button type="button" onclick="location.reload()" class="btn w-full bg-gold/35 text-ink shadow-[3px_3px_0_0_var(--color-ink)] hover:bg-gold/55"><x-icon name="refresh" class="h-5 w-5 shrink-0" />ページを再読み込み</button>
    </x-slot:actions>
</x-error-page>
