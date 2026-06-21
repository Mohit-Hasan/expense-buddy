<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'account_title',
        'account_number',
        'currency_id',
        'initial_balance',
        'current_balance',
        'note',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'initial_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
