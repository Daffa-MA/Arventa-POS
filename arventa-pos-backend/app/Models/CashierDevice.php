<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierDevice extends Model
{
    protected $fillable = [
        'pos_instance_id',
        'user_id',
        'device_name',
        'device_uid',
        'paired_at',
        'last_seen_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posInstance(): BelongsTo
    {
        return $this->belongsTo(PosInstance::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
