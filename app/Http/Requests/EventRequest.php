<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /**
     * 「ハズレを含む／含まない」が送られなかったときは「含まない」にする。
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['count_misses_toward_limit' => $this->boolean('count_misses_toward_limit')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_prizes_per_participant' => ['nullable', 'integer', 'min:1', 'max:999'],
            'count_misses_toward_limit' => ['boolean'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'copy_from' => ['nullable', 'integer', Rule::exists('events', 'id')->where('user_id', $this->user()->id)],
            'copy_prizes' => ['boolean'],
            // 開催予定。3つそろえて入れるか、すべて空欄（あとで入れる）
            'scheduled_date' => ['nullable', 'date_format:Y-m-d', 'after:2020-01-01', 'before:2100-01-01', 'required_with:scheduled_time'],
            'scheduled_time' => ['nullable', 'date_format:H:i', 'required_with:scheduled_date'],
            'scheduled_minutes' => ['nullable', 'integer', Rule::in(array_keys(Event::SCHEDULE_LENGTHS)), 'required_with:scheduled_date'],
            // 開催時間は選択肢なので、日付が空欄のときは使わない（schedule() で null にする）
        ];
    }

    /**
     * 開催予定を、保存する形（日本時間の日時と分）にする。空欄なら null。
     *
     * @return array{scheduled_start_at: Carbon|null, scheduled_minutes: int|null}
     */
    public function schedule(): array
    {
        if (! $this->filled('scheduled_date')) {
            return ['scheduled_start_at' => null, 'scheduled_minutes' => null];
        }

        return [
            'scheduled_start_at' => Carbon::createFromFormat('Y-m-d H:i', $this->input('scheduled_date').' '.$this->input('scheduled_time'), config('app.timezone')),
            'scheduled_minutes' => (int) $this->input('scheduled_minutes'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'イベント名',
            'description' => '説明',
            'max_prizes_per_participant' => '1人あたりの獲得上限',
            'time_limit_minutes' => '制限時間',
            'copy_from' => 'コピー元のイベント',
            'scheduled_date' => '開催日',
            'scheduled_time' => '開始時刻',
            'scheduled_minutes' => '開催時間',
        ];
    }
}
