<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'pos_instance_id')) {
                $table->foreignId('pos_instance_id')->nullable()->after('is_active')->constrained('pos_instances')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('users', 'role')) {
            DB::statement($this->roleColumnSql());
        }

        if (Schema::hasColumn('pos_instances', 'status')) {
            DB::statement($this->posStatusColumnSql());
        }

        Schema::table('pos_instances', function (Blueprint $table): void {
            if (! Schema::hasColumn('pos_instances', 'buyer_name')) {
                $table->string('buyer_name')->nullable()->after('store_name');
            }

            if (! Schema::hasColumn('pos_instances', 'contact')) {
                $table->string('contact')->nullable()->after('buyer_name');
            }

            if (! Schema::hasColumn('pos_instances', 'package_name')) {
                $table->string('package_name')->nullable()->unique()->after('database_name');
            }

            if (! Schema::hasColumn('pos_instances', 'admin_password_hash')) {
                $table->string('admin_password_hash')->nullable()->after('admin_password');
            }

            if (! Schema::hasColumn('pos_instances', 'deployment_status')) {
                $table->string('deployment_status')->default('pending')->after('status');
            }

            if (! Schema::hasColumn('pos_instances', 'deployment_error')) {
                $table->text('deployment_error')->nullable()->after('deployment_status');
            }

            if (! Schema::hasColumn('pos_instances', 'deployed_at')) {
                $table->timestamp('deployed_at')->nullable()->after('deployment_error');
            }
        });

        DB::table('pos_instances')->orderBy('id')->get()->each(function (object $instance): void {
            DB::table('pos_instances')
                ->where('id', $instance->id)
                ->update([
                    'buyer_name' => $instance->buyer_name ?? $instance->owner_name ?? null,
                    'contact' => $instance->contact ?? $instance->owner_phone ?? null,
                    'package_name' => $instance->package_name ?? $instance->app_package_name ?? null,
                    'status' => match ($instance->status) {
                        'draft', 'provisioning' => 'pending',
                        default => $instance->status,
                    },
                    'deployment_status' => $instance->deployment_status ?? ($instance->provisioned_at ? 'deployed' : 'pending'),
                    'deployed_at' => $instance->deployed_at ?? $instance->provisioned_at ?? null,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('pos_instances', function (Blueprint $table): void {
            foreach (['buyer_name', 'contact', 'package_name', 'admin_password_hash', 'deployment_status', 'deployment_error', 'deployed_at'] as $column) {
                if (Schema::hasColumn('pos_instances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'pos_instance_id')) {
                $table->dropConstrainedForeignId('pos_instance_id');
            }
        });
    }

    private function roleColumnSql(): string
    {
        return match (DB::getDriverName()) {
            'mysql' => "ALTER TABLE users MODIFY role VARCHAR(40) NOT NULL DEFAULT 'admin'",
            'pgsql' => "ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(40), ALTER COLUMN role SET DEFAULT 'admin'",
            default => "UPDATE users SET role = role",
        };
    }

    private function posStatusColumnSql(): string
    {
        return match (DB::getDriverName()) {
            'mysql' => "ALTER TABLE pos_instances MODIFY status VARCHAR(40) NOT NULL DEFAULT 'pending'",
            'pgsql' => "ALTER TABLE pos_instances ALTER COLUMN status TYPE VARCHAR(40), ALTER COLUMN status SET DEFAULT 'pending'",
            default => "UPDATE pos_instances SET status = status",
        };
    }
};
