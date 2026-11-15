<x-errors />

<div>
    <label for="name" class="label">イベント名</label>
    <input id="name" name="name" type="text" value="{{ old('name', $event->name) }}" class="field" required maxlength="100" placeholder="例：秋の宝探し大会">
</div>
@php
    $scheduledAt = $event->scheduled_start_at;
    $scheduleDate = old('scheduled_date', $scheduledAt?->format('Y-m-d'));
    $scheduleTime = old('scheduled_time', $scheduledAt?->format('H:i'));
    $scheduleMinutes = (int) old('scheduled_minutes', $event->scheduled_minutes ?? 120);
@endphp
<fieldset id="schedule" class="scroll-mt-4 rounded-2xl border-2 border-sea bg-white/70 p-4 shadow-[3px_3px_0_0_var(--color-sea)]" data-schedule-fields>
    <legend class="px-1 text-base font-black">開催の予定</legend>
    <p class="text-xs text-ink-soft">今決まっていなければ空欄でも作れます。お試しモードは予定なしで使えますが、本番（宝探しスタート・参加の受付）を始めるには予定が必要です。雨で延期になっても、あとから変えられます。</p>
    <div class="mt-3 grid gap-3 sm:grid-cols-3">
        <div>
            <label for="scheduled_date" class="label">開催日</label>
            <input id="scheduled_date" name="scheduled_date" type="date" value="{{ $scheduleDate }}" class="field">
        </div>
        <div>
            <label for="scheduled_time" class="label">開始時刻</label>
            <input id="scheduled_time" name="scheduled_time" type="time" step="300" value="{{ $scheduleTime }}" class="field">
        </div>
        <div>
            <label for="scheduled_minutes" class="label">開催時間</label>
            <select id="scheduled_minutes" name="scheduled_minutes" class="field">
                @foreach (\App\Models\Event::SCHEDULE_LENGTHS as $minutes => $label)
                    <option value="{{ $minutes }}" @selected($scheduleMinutes === $minutes)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <p class="mt-2 text-xs text-ink-soft">受付から景品の引き換えまでを含めた、だいたいの時間で大丈夫です。</p>
</fieldset>
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
    <p class="mt-1 text-xs text-ink-soft">空欄にすると上限なしになります。上限に達した参加者は、それ以上お宝の宝箱を開けられなくなります（ハズレも開けられません）。</p>
</div>
<div>
    <label for="time_limit_minutes" class="label">制限時間</label>
    <div class="flex items-center gap-2">
        <input id="time_limit_minutes" name="time_limit_minutes" type="number" min="1" max="600" inputmode="numeric" value="{{ old('time_limit_minutes', $event->time_limit_minutes) }}" class="field w-28" placeholder="なし">
        <span class="font-bold">分</span>
    </div>
    <p class="mt-1 text-xs text-ink-soft">空欄にすると制限時間なしになります。時間はイベントページの「宝探しスタート」を押したときから数えます。</p>
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
