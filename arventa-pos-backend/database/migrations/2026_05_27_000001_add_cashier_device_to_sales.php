<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('cashier_device_id')
                ->nullable()
                ->after('cashier_id')
                ->constrained('cashier_devices')
                ->nullOnDelete();
            $table->string('cashier_device_name')->nullable()->after('cashier_device_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cashier_device_id');
            $table->dropColumn('cashier_device_name');
        });
    }
};
