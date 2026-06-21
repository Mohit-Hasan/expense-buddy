<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
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
        $type = (string) $this->input('type');

        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'type' => ['required', 'string', Rule::in(['income', 'expense', 'lending'])],
            'category_id' => [
                Rule::excludeIf($type === 'lending'),
                'nullable',
                'integer',
                'exists:transaction_categories,id',
            ],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,4})?$/'],
            'rate_at_transaction' => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,4})?$/'],
            'contact_id' => [
                Rule::requiredIf($type === 'lending'),
                'nullable',
                'integer',
                'exists:contacts,id',
            ],
            'transaction_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
