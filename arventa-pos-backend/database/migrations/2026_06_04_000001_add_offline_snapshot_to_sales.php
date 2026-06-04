<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('client_sale_id')->nullable()->after('invoice_number');
            $table->timestamp('client_created_at')->nullable()->after('cashier_device_name');
            $table->timestamp('catalog_synced_at')->nullable()->after('client_created_at');
            $table->string('sync_source')->default('online')->after('payment_method');

            $table->unique(['pos_instance_id', 'client_sale_id'], 'sales_pos_client_sale_unique');
            $table->index(['pos_instance_id', 'sync_source'], 'sales_pos_sync_source_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique('sales_pos_client_sale_unique');
            $table->dropIndex('sales_pos_sync_source_index');
            $table->dropColumn([
                'client_sale_id',
                'client_created_at',
                'catalog_synced_at',
                'sync_source',
            ]);
        });
    }
};
