<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->boolean('receipt_show_logo')->default(false)->after('receipt_paper_size');
            $table->boolean('receipt_show_address')->default(true)->after('receipt_show_logo');
            $table->boolean('receipt_show_datetime')->default(true)->after('receipt_show_address');
            $table->boolean('receipt_show_qris')->default(false)->after('receipt_show_datetime');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'receipt_show_logo',
                'receipt_show_address',
                'receipt_show_datetime',
                'receipt_show_qris',
            ]);
        });
    }
};
