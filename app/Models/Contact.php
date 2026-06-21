<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'company',
        'address',
        'current_balance',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'current_balance' => 'decimal:4',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
