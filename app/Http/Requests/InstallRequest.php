<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\AppInstall;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class InstallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! AppInstall::isInstalled();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'system_name' => ['required', 'string', 'max:120'],
            'system_logo' => ['required', 'image', 'max:2048'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
            'currency_name' => ['required', 'string', 'max:120'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'allow_negative_balances' => ['sometimes', 'boolean'],
        ];
    }
}
