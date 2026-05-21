<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->enum('pos_orientation', ['portrait', 'landscape'])->default('portrait')->after('product_card_style');
            $table->boolean('show_search_on_app')->default(true)->after('show_stock_on_app');
            $table->boolean('show_cart_on_app')->default(true)->after('show_search_on_app');
            $table->enum('cart_position', ['bottom', 'right'])->default('bottom')->after('show_cart_on_app');
            $table->enum('checkout_position', ['bottom', 'floating', 'cart'])->default('bottom')->after('cart_position');
            $table->boolean('show_order_summary_on_app')->default(true)->after('checkout_position');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'pos_orientation',
                'show_search_on_app',
                'show_cart_on_app',
                'cart_position',
                'checkout_position',
                'show_order_summary_on_app',
            ]);
        });
    }
};
