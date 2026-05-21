<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('admin_brand_name')->default('Arventa POS')->after('logo_path');
            $table->string('admin_console_label')->default('Admin Console')->after('admin_brand_name');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn(['admin_brand_name', 'admin_console_label']);
        });
    }
};
