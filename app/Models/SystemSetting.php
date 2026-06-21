<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'system_name',
        'system_logo',
        'default_currency_id',
        'allow_negative_balances',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'allow_negative_balances' => 'boolean',
    ];

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }
}
