<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'business_type',
        'logo_path',
        'theme_color',
        'admin_theme_color',
        'admin_sidebar_style',
        'admin_density',
        'app_layout',
        'product_card_style',
        'show_sku_on_app',
        'show_stock_on_app',
        'address',
        'receipt_footer',
        'tax_rate',
        'service_charge_rate',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'service_charge_rate' => 'decimal:2',
            'show_sku_on_app' => 'boolean',
            'show_stock_on_app' => 'boolean',
        ];
    }
}
