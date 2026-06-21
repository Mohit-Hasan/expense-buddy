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
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'allow_negative_balances' => 'boolean',
        'mail_port' => 'integer',
    ];

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }
}
