<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_instances', function (Blueprint $table): void {
            $table->id();
            $table->string('store_name');
            $table->string('owner_name')->nullable();
            $table->string('owner_phone')->nullable();
            $table->string('subdomain')->unique();
            $table->string('domain')->unique();
            $table->string('database_name')->unique();
            $table->string('admin_username');
            $table->text('admin_password');
            $table->string('app_package_name')->nullable();
            $table->enum('status', ['draft', 'provisioning', 'active', 'suspended'])->default('draft');
            $table->timestamp('provisioned_at')->nullable();
            $table->text('deployment_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_instances');
    }
};
