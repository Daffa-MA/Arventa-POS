<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('receipt_qr_image_path')->nullable()->after('qris_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn('receipt_qr_image_path');
        });
    }
};
