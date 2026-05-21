<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'business_type',
        'logo_path',
        'qris_image_path',
        'admin_brand_name',
        'admin_console_label',
        'theme_color',
        'app_text_color',
        'app_secondary_text_color',
        'app_price_text_color',
        'admin_theme_color',
        'admin_sidebar_style',
        'admin_density',
        'app_layout',
        'product_card_style',
        'pos_orientation',
        'show_sku_on_app',
        'show_stock_on_app',
        'show_search_on_app',
        'show_cart_on_app',
        'cart_position',
        'checkout_position',
        'show_order_summary_on_app',
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
            'show_search_on_app' => 'boolean',
            'show_cart_on_app' => 'boolean',
            'show_order_summary_on_app' => 'boolean',
        ];
    }
}
