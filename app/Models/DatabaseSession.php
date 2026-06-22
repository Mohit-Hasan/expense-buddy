<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DatabaseSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'sessions';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastActiveAt(): Carbon
    {
        return Carbon::createFromTimestamp((int) $this->last_activity);
    }
}
