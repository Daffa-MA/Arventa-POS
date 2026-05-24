<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('store_name');
            $table->string('business_type')->default('retail');
            $table->string('logo_path')->nullable();
            $table->string('theme_color', 7)->default('#2563EB');
            $table->enum('app_layout', ['grid', 'list', 'compact'])->default('grid');
            $table->enum('product_card_style', ['image', 'minimal'])->default('minimal');
            $table->boolean('show_sku_on_app')->default(false);
            $table->boolean('show_stock_on_app')->default(true);
            $table->string('address')->nullable();
            $table->string('receipt_footer')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('service_charge_rate', 5, 2)->default(0);
            $table->string('currency')->default('IDR');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->enum('type', ['product', 'service', 'discount', 'fee', 'custom'])->default('product');
            $table->enum('unit', ['pcs', 'ml', 'gram', 'kg', 'meter', 'trx'])->default('pcs');
            $table->decimal('price', 12, 2);
            $table->decimal('stock', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('service_charge_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->decimal('paid_amount', 12, 2);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('payment_method')->default('cash');
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 12, 3);
            $table->enum('unit', ['pcs', 'ml', 'gram', 'kg', 'meter', 'trx'])->default('pcs');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('products');
        Schema::dropIfExists('store_settings');
    }
};
