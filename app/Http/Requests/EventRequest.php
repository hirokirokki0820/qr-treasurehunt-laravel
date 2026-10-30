<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            'copy_from' => ['nullable', 'integer', Rule::exists('events', 'id')->where('user_id', $this->user()->id)],
            'copy_prizes' => ['boolean'],
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
            'copy_from' => 'コピー元のイベント',
        ];
    }
}
