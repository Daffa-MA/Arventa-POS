<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('app_text_color', 7)->default('#0F172A')->after('theme_color');
            $table->string('app_secondary_text_color', 7)->default('#64748B')->after('app_text_color');
            $table->string('app_price_text_color', 7)->default('#0F172A')->after('app_secondary_text_color');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn(['app_text_color', 'app_secondary_text_color', 'app_price_text_color']);
        });
    }
};
