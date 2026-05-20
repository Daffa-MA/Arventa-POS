<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Arventa',
            'email' => 'admin@arventa.test',
            'username' => 'admin',
            'role' => 'admin',
            'is_active' => true,
        ]);

        StoreSetting::query()->create([
            'store_name' => 'Arventa POS',
            'business_type' => 'retail',
            'theme_color' => '#2563EB',
            'admin_theme_color' => '#0F172A',
            'admin_sidebar_style' => 'light',
            'admin_density' => 'comfortable',
            'app_layout' => 'grid',
            'product_card_style' => 'minimal',
            'show_sku_on_app' => false,
            'show_stock_on_app' => true,
            'address' => null,
            'receipt_footer' => 'Terima kasih.',
            'tax_rate' => 11,
            'service_charge_rate' => 0,
            'currency' => 'IDR',
        ]);
    }
}
