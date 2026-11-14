<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PrizeRequest extends FormRequest
{
    /**
     * ハズレの景品名は、いつも「ハズレ」にする（ハズレなのに景品名があると、受付で取り違えやすいため）。
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_miss' => $this->boolean('is_miss')]);

        if (! $this->boolean('is_miss')) {
            return;
        }

        $this->merge(['name' => 'ハズレ']);
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
