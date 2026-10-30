<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PrizeRequest extends FormRequest
{
    /**
     * ハズレの場合、登録時は景品名が空なら「ハズレ」にする。
     * 編集でハズレにしたときは、景品名も「ハズレ」に書き換える。
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_miss' => $this->boolean('is_miss')]);

        if (! $this->boolean('is_miss')) {
            return;
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH') || blank($this->input('name'))) {
            $this->merge(['name' => 'ハズレ']);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_miss' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '景品名',
            'description' => 'メッセージ',
        ];
    }
}
