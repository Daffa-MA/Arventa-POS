<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE products MODIFY type ENUM('product', 'service', 'discount', 'fee', 'custom') NOT NULL DEFAULT 'product'");
        DB::statement("ALTER TABLE products MODIFY unit ENUM('pcs', 'ml', 'gram', 'kg', 'meter', 'trx') NOT NULL DEFAULT 'pcs'");
        DB::statement("ALTER TABLE sale_items MODIFY unit ENUM('pcs', 'ml', 'gram', 'kg', 'meter', 'trx') NOT NULL DEFAULT 'pcs'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE products SET type = 'service' WHERE type IN ('discount', 'fee', 'custom')");
        DB::statement("UPDATE products SET unit = 'pcs' WHERE unit = 'trx'");
        DB::statement("UPDATE sale_items SET unit = 'pcs' WHERE unit = 'trx'");
        DB::statement("ALTER TABLE products MODIFY type ENUM('product', 'service') NOT NULL DEFAULT 'product'");
        DB::statement("ALTER TABLE products MODIFY unit ENUM('pcs', 'ml', 'gram', 'kg', 'meter') NOT NULL DEFAULT 'pcs'");
        DB::statement("ALTER TABLE sale_items MODIFY unit ENUM('pcs', 'ml', 'gram', 'kg', 'meter') NOT NULL DEFAULT 'pcs'");
    }
};
