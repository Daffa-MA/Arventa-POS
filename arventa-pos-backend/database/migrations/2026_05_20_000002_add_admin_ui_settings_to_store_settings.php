<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('admin_theme_color', 7)->default('#0F172A')->after('theme_color');
            $table->enum('admin_sidebar_style', ['light', 'dark', 'accent'])->default('light')->after('admin_theme_color');
            $table->enum('admin_density', ['comfortable', 'compact'])->default('comfortable')->after('admin_sidebar_style');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn(['admin_theme_color', 'admin_sidebar_style', 'admin_density']);
        });
    }
};
