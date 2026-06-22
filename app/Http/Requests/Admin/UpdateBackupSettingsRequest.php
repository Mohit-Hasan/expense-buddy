<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'backup_enabled' => ['nullable', 'boolean'],
            'backup_frequency' => ['required', 'string', Rule::in(['weekly', 'monthly', 'custom'])],
            'backup_day' => ['required', 'integer', 'min:0', 'max:365'],
            'backup_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'backup_enabled' => $this->boolean('backup_enabled'),
        ]);
    }
}
