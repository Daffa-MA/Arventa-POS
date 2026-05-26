<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('receipt_header_title')->nullable()->after('receipt_footer');
            $table->string('receipt_header_subtitle')->nullable()->after('receipt_header_title');
            $table->text('receipt_header_notes')->nullable()->after('receipt_header_subtitle');
            $table->string('receipt_header_alignment', 12)->default('center')->after('receipt_header_notes');
            $table->boolean('receipt_show_store_name')->default(true)->after('receipt_header_alignment');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'receipt_header_title',
                'receipt_header_subtitle',
                'receipt_header_notes',
                'receipt_header_alignment',
                'receipt_show_store_name',
            ]);
        });
    }
};
