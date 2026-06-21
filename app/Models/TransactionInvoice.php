<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionInvoice extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'public_token',
        'expires_at',
        'is_public',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isAccessible(): bool
    {
        if (! $this->is_public) {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }
}
