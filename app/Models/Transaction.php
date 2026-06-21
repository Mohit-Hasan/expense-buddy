<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'type',
        'category_id',
        'payment_method_id',
        'currency_id',
        'amount',
        'rate_at_transaction',
        'contact_id',
        'transaction_date',
        'reference',
        'description',
        'attachment',
        'transfer_reference_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:4',
        'rate_at_transaction' => 'decimal:4',
        'transaction_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function transferReference(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transfer_reference_id');
    }

    public function linkedTransfers(): HasMany
    {
        return $this->hasMany(self::class, 'transfer_reference_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(TransactionInvoice::class);
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(TransactionInvoice::class)->latestOfMany();
    }
}
