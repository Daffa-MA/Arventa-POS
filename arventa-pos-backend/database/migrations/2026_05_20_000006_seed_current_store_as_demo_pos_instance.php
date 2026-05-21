<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_settings') || ! Schema::hasTable('pos_instances')) {
            return;
        }

        if (DB::table('pos_instances')->exists()) {
            return;
        }

        $setting = DB::table('store_settings')->first();

        if (! $setting) {
            return;
        }

        $subdomain = Str::slug($setting->store_name) ?: 'arventa-demo-store';
        $databaseName = 'arventa_pos_'.str_replace('-', '_', $subdomain);
        $domain = $subdomain.'.arventapos.local';
        $now = now();

        DB::table('pos_instances')->insert([
            'store_name' => $setting->store_name,
            'owner_name' => 'Demo Arventa',
            'owner_phone' => null,
            'subdomain' => $subdomain,
            'domain' => $domain,
            'database_name' => $databaseName,
            'admin_username' => 'admin_'.str_replace('-', '_', $subdomain),
            'admin_password' => Crypt::encryptString('Arv-DEMO-12345'),
            'app_package_name' => 'com.arventapos.'.str_replace('-', '', $subdomain),
            'status' => 'active',
            'provisioned_at' => $now,
            'deployment_notes' => implode(PHP_EOL, [
                'POS contoh dari admin toko lokal.',
                'Domain demo: '.$domain,
                'Database demo: '.$databaseName,
                'Gunakan record ini sebagai contoh format saat generate POS pembeli.',
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('pos_instances')) {
            return;
        }

        DB::table('pos_instances')
            ->where('owner_name', 'Demo Arventa')
            ->delete();
    }
};
