<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_instances')) {
            return;
        }

        $defaultPosInstanceId = $this->defaultPosInstanceId();

        $this->addPosInstanceId('store_settings', $defaultPosInstanceId);
        $this->addPosInstanceId('products', $defaultPosInstanceId);
        $this->addPosInstanceId('sales', $defaultPosInstanceId);
        $this->addPosInstanceId('cashier_pairing_codes', $defaultPosInstanceId);
        $this->addPosInstanceId('cashier_devices', $defaultPosInstanceId);

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'pos_instance_id')) {
            DB::table('users')
                ->whereIn('role', ['admin', 'cashier'])
                ->whereNull('pos_instance_id')
                ->update(['pos_instance_id' => $defaultPosInstanceId]);
        }

        if (Schema::hasTable('products')) {
            $this->scopeProductSkuUniqueness();
        }

        if (Schema::hasTable('cashier_devices')) {
            $this->scopeDeviceUidUniqueness();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                try {
                    $table->dropUnique('products_pos_instance_id_sku_unique');
                } catch (Throwable) {
                    //
                }

                try {
                    $table->unique('sku');
                } catch (Throwable) {
                    //
                }
            });
        }

        if (Schema::hasTable('cashier_devices')) {
            Schema::table('cashier_devices', function (Blueprint $table): void {
                try {
                    $table->dropUnique('cashier_devices_pos_instance_id_device_uid_unique');
                } catch (Throwable) {
                    //
                }

                try {
                    $table->unique('device_uid');
                } catch (Throwable) {
                    //
                }
            });
        }

        foreach (['cashier_devices', 'cashier_pairing_codes', 'sales', 'products', 'store_settings'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'pos_instance_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if ($this->hasForeignKey($tableName, $tableName.'_pos_instance_id_foreign')) {
                        $table->dropForeign($tableName.'_pos_instance_id_foreign');
                    }

                    $table->dropColumn('pos_instance_id');
                });
            }
        }
    }

    private function addPosInstanceId(string $tableName, int $defaultPosInstanceId): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasColumn($tableName, 'pos_instance_id')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('pos_instance_id')->nullable()->after('id')->constrained('pos_instances')->nullOnDelete();
            });
        }

        DB::table($tableName)
            ->whereNull('pos_instance_id')
            ->update(['pos_instance_id' => $defaultPosInstanceId]);
    }

    private function defaultPosInstanceId(): int
    {
        $existing = DB::table('pos_instances')->orderBy('id')->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $setting = Schema::hasTable('store_settings')
            ? DB::table('store_settings')->orderBy('id')->first()
            : null;

        $storeName = $setting?->store_name ?: 'Arventa POS';
        $subdomain = Str::slug($storeName) ?: 'arventa-pos';
        $now = now();

        return (int) DB::table('pos_instances')->insertGetId([
            'store_name' => $storeName,
            'buyer_name' => 'Demo Arventa',
            'contact' => null,
            'owner_name' => 'Demo Arventa',
            'owner_phone' => null,
            'subdomain' => $subdomain,
            'domain' => $subdomain.'.arventa.my.id',
            'database_name' => 'arventa_pos_'.str_replace('-', '_', $subdomain),
            'package_name' => 'com.arventapos.'.str_replace('-', '', $subdomain),
            'admin_username' => 'admin_'.str_replace('-', '_', $subdomain),
            'admin_password' => Crypt::encryptString('Arv-DEMO-12345'),
            'admin_password_hash' => bcrypt('Arv-DEMO-12345'),
            'app_package_name' => 'com.arventapos.'.str_replace('-', '', $subdomain),
            'status' => 'active',
            'deployment_status' => 'deployed',
            'deployed_at' => $now,
            'provisioned_at' => $now,
            'deployment_notes' => 'POS default untuk data lama.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function scopeProductSkuUniqueness(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            try {
                $table->dropUnique('products_sku_unique');
            } catch (Throwable) {
                //
            }

            try {
                $table->unique(['pos_instance_id', 'sku']);
            } catch (Throwable) {
                //
            }
        });
    }

    private function scopeDeviceUidUniqueness(): void
    {
        Schema::table('cashier_devices', function (Blueprint $table): void {
            try {
                $table->dropUnique('cashier_devices_device_uid_unique');
            } catch (Throwable) {
                //
            }

            try {
                $table->unique(['pos_instance_id', 'device_uid']);
            } catch (Throwable) {
                //
            }
        });
    }

    private function hasForeignKey(string $tableName, string $foreignKeyName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $foreignKeyName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
