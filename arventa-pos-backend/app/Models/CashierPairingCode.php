<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierPairingCode extends Model
{
    protected $fillable = [
        'pos_instance_id',
        'code',
        'cashier_name',
        'device_label',
        'expires_at',
        'paired_at',
        'paired_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'paired_at' => 'datetime',
        ];
    }

    public function pairedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paired_user_id');
    }

    public function posInstance(): BelongsTo
    {
        return $this->belongsTo(PosInstance::class);
    }
}
