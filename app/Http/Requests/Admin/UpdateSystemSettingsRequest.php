<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('menu.admin.settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'system_name' => ['required', 'string', 'max:120'],
            'default_currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'allow_negative_balances' => ['sometimes', 'boolean'],
            'system_logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
