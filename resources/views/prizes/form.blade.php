<x-errors />

<div>
    <label for="name" class="label">景品名</label>
    <input id="name" name="name" type="text" value="{{ old('name', $prize->name) }}" class="field" maxlength="100" placeholder="例：お菓子の詰め合わせ（ハズレの場合は空欄でもOK）" @if (! $prize->exists) autofocus @endif>
</div>
<div>
    <label for="description" class="label">メッセージ（宝箱を開けたときに表示されます・空欄でもOK）</label>
    <textarea id="description" name="description" rows="3" class="field" maxlength="1000" placeholder="例：受付で交換してね！（空欄でもOK）">{{ old('description', $prize->description) }}</textarea>
</div>
<label class="flex items-start gap-3 rounded-xl border-2 border-ink/20 bg-parchment/60 p-3">
    <input type="checkbox" name="is_miss" value="1" class="mt-1 h-4 w-4 accent-sea" @checked(old('is_miss', $prize->is_miss))>
    <span>
        <span class="block font-bold">ハズレにする</span>
        <span class="block text-sm text-ink-soft">ハズレは何度読み取っても在庫が減りません。</span>
    </span>
</label>
