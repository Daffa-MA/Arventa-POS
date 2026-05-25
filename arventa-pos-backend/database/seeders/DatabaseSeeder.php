<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use App\Models\PosInstance;
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
        User::query()->firstOrCreate([
            'email' => 'admin@arventa.test',
        ], [
            'name' => 'Admin Arventa',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        if (config('services.arventa_developer.password') && config('services.arventa_developer.username')) {
            User::query()->updateOrCreate([
                'username' => config('services.arventa_developer.username'),
            ], [
                'name' => config('services.arventa_developer.name', 'Arventa Developer'),
                'email' => config('services.arventa_developer.email') ?: 'developer@arventa.local',
                'password' => config('services.arventa_developer.password'),
                'role' => 'developer',
                'is_active' => true,
            ]);
        }

        $setting = StoreSetting::query()->firstOrCreate([], [
            'store_name' => 'Arventa POS',
            'business_type' => 'retail',
            'admin_brand_name' => 'Arventa POS',
            'admin_console_label' => 'Admin Console',
            'theme_color' => '#2563EB',
            'app_text_color' => '#0F172A',
            'app_secondary_text_color' => '#64748B',
            'app_price_text_color' => '#0F172A',
            'admin_theme_color' => '#0F172A',
            'admin_sidebar_style' => 'light',
            'admin_density' => 'comfortable',
            'app_layout' => 'grid',
            'product_card_style' => 'minimal',
            'pos_orientation' => 'portrait',
            'show_sku_on_app' => false,
            'show_stock_on_app' => true,
            'show_search_on_app' => true,
            'show_cart_on_app' => true,
            'cart_position' => 'bottom',
            'checkout_position' => 'bottom',
            'show_order_summary_on_app' => true,
            'address' => null,
            'receipt_footer' => 'Terima kasih.',
            'tax_rate' => 11,
            'service_charge_rate' => 0,
            'currency' => 'IDR',
        ]);

        PosInstance::query()->firstOrCreate([
            'subdomain' => 'arventa-pos',
        ], [
            'store_name' => $setting->store_name,
            'buyer_name' => 'Demo Arventa',
            'contact' => null,
            'owner_name' => 'Demo Arventa',
            'owner_phone' => null,
            'subdomain' => 'arventa-pos',
            'domain' => 'arventa-pos.arventapos.local',
            'database_name' => 'arventa_pos_arventa_pos',
            'package_name' => 'com.arventapos.arventapos',
            'admin_username' => 'admin_arventa_pos',
            'admin_password' => 'Arv-DEMO-12345',
            'admin_password_hash' => bcrypt('Arv-DEMO-12345'),
            'app_package_name' => 'com.arventapos.arventapos',
            'status' => 'active',
            'deployment_status' => 'deployed',
            'deployed_at' => now(),
            'provisioned_at' => now(),
            'deployment_notes' => implode(PHP_EOL, [
                'POS contoh dari admin toko lokal.',
                'Domain demo: arventa-pos.arventapos.local',
                'Database demo: arventa_pos_arventa_pos',
                'Gunakan record ini sebagai contoh format saat generate POS pembeli.',
            ]),
        ]);
    }
}
