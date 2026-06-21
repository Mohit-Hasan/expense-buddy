<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'status',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
