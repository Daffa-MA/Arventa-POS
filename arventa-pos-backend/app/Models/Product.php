<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'pos_instance_id',
        'name',
        'sku',
        'image_path',
        'type',
        'unit',
        'price',
        'stock',
        'free_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'decimal:3',
            'free_quantity' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function posInstance(): BelongsTo
    {
        return $this->belongsTo(PosInstance::class);
    }
}
