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
            'mail_driver' => ['required', 'in:smtp,sendmail'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
