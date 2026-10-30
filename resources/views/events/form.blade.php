<x-errors />

<div>
    <label for="name" class="label">イベント名</label>
    <input id="name" name="name" type="text" value="{{ old('name', $event->name) }}" class="field" required maxlength="100" placeholder="例：秋の宝探し大会">
</div>
<div>
    <label for="description" class="label">説明（参加者の画面に表示されます）</label>
    <textarea id="description" name="description" rows="3" class="field" maxlength="1000" placeholder="例：制限時間は30分。1人3個まで！">{{ old('description', $event->description) }}</textarea>
</div>
<div>
    <label for="max_prizes_per_participant" class="label">1人あたりの獲得上限</label>
    <div class="flex items-center gap-2">
        <input id="max_prizes_per_participant" name="max_prizes_per_participant" type="number" min="1" max="999" inputmode="numeric" value="{{ old('max_prizes_per_participant', $event->max_prizes_per_participant) }}" class="field w-28" placeholder="なし">
        <span class="font-bold">個まで</span>
    </div>
    <p class="mt-1 text-xs text-ink-soft">空欄にすると上限なしになります。上限に達した参加者は、それ以上QRコードを読み取れなくなります。</p>
</div>
@php
    $countsMisses = (bool) old('count_misses_toward_limit', $event->count_misses_toward_limit ?? false);
@endphp
<fieldset>
    <legend class="label">ハズレを獲得上限に</legend>
    <div class="grid gap-2 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 border-ink/20 bg-white p-3 has-[:checked]:border-sea has-[:checked]:bg-sea/5">
            <input type="radio" name="count_misses_toward_limit" value="0" class="mt-1 h-4 w-4 accent-sea" @checked(! $countsMisses)>
            <span>
                <span class="block font-bold">含まない</span>
                <span class="block text-xs text-ink-soft">当たりの景品だけを数えます。</span>
            </span>
        </label>
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 border-ink/20 bg-white p-3 has-[:checked]:border-sea has-[:checked]:bg-sea/5">
            <input type="radio" name="count_misses_toward_limit" value="1" class="mt-1 h-4 w-4 accent-sea" @checked($countsMisses)>
            <span>
                <span class="block font-bold">含む</span>
                <span class="block text-xs text-ink-soft">開けたハズレも1個として数えます（同じハズレは1回だけ）。</span>
            </span>
        </label>
    </div>
    <p class="mt-1 text-xs text-ink-soft">獲得上限が空欄（上限なし）のときは、この設定は関係ありません。</p>
</fieldset>
