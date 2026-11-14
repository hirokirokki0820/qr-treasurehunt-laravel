<x-errors />

@php($isMiss = (bool) old('is_miss', $prize->is_miss))
{{-- 種類は最初に選ぶ。チェックの付け忘れで「ハズレ」が当たりになるのを防ぐため、当たり／ハズレのどちらかを選ぶ形にしている --}}
<fieldset>
    <legend class="label">種類</legend>
    <div class="grid grid-cols-2 gap-3">
        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border-2 border-ink/25 bg-white px-4 py-3 has-[:checked]:border-ink has-[:checked]:bg-gold/30 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-sea">
            <input type="radio" name="is_miss" value="0" class="h-5 w-5 accent-ink" @checked(! $isMiss) data-prize-kind>
            <span><span class="block font-black">当たり</span><span class="block text-xs text-ink-soft">景品がもらえる</span></span>
        </label>
        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border-2 border-ink/25 bg-white px-4 py-3 has-[:checked]:border-ink has-[:checked]:bg-ink/10 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-sea">
            <input type="radio" name="is_miss" value="1" class="h-5 w-5 accent-ink" @checked($isMiss) data-prize-kind>
            <span><span class="block font-black">ハズレ</span><span class="block text-xs text-ink-soft">何度開けても在庫が減らない</span></span>
        </label>
    </div>
</fieldset>

<div>
    <label for="name" class="label">景品名</label>
    <input id="name" name="name" type="text" value="{{ old('name', $prize->name) }}" class="field" maxlength="100" placeholder="例：お菓子の詰め合わせ（ハズレの場合は空欄でもOK）" @if (! $prize->exists) autofocus @endif>
</div>
<div>
    <label for="description" class="label">メッセージ（宝箱を開けたときに表示されます・空欄でもOK）</label>
    <textarea id="description" name="description" rows="3" class="field" maxlength="1000" placeholder="例：受付で交換してね！（空欄でもOK）">{{ old('description', $prize->description) }}</textarea>
</div>

