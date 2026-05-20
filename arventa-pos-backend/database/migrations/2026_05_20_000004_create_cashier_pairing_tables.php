<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('email');
            $table->enum('role', ['admin', 'cashier'])->default('admin')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('cashier_pairing_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 6)->unique();
            $table->string('cashier_name');
            $table->string('device_label')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('paired_at')->nullable();
            $table->foreignId('paired_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cashier_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name');
            $table->string('device_uid')->nullable()->unique();
            $table->timestamp('paired_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_devices');
        Schema::dropIfExists('cashier_pairing_codes');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
