<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('receipt_template', 24)->default('classic')->after('receipt_footer');
            $table->string('receipt_paper_size', 8)->default('58')->after('receipt_template');
            $table->boolean('receipt_show_business_type')->default(true)->after('receipt_paper_size');
            $table->boolean('receipt_show_payment_method')->default(true)->after('receipt_show_business_type');
            $table->boolean('receipt_show_item_price')->default(true)->after('receipt_show_payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'receipt_template',
                'receipt_paper_size',
                'receipt_show_business_type',
                'receipt_show_payment_method',
                'receipt_show_item_price',
            ]);
        });
    }
};
