<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'pos_instance_id',
        'invoice_number',
        'cashier_id',
        'cashier_device_id',
        'cashier_device_name',
        'subtotal',
        'tax_total',
        'service_charge_total',
        'grand_total',
        'paid_amount',
        'change_amount',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'service_charge_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    public function posInstance(): BelongsTo
    {
        return $this->belongsTo(PosInstance::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function cashierDevice(): BelongsTo
    {
        return $this->belongsTo(CashierDevice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
