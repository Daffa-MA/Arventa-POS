<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosInstance extends Model
{
    protected $fillable = [
        'store_name',
        'buyer_name',
        'contact',
        'owner_name',
        'owner_phone',
        'subdomain',
        'domain',
        'database_name',
        'package_name',
        'admin_username',
        'admin_password',
        'admin_password_hash',
        'app_package_name',
        'status',
        'deployment_status',
        'deployment_error',
        'deployed_at',
        'provisioned_at',
        'deployment_notes',
    ];

    protected function casts(): array
    {
        return [
            'admin_password' => 'encrypted',
            'deployed_at' => 'datetime',
            'provisioned_at' => 'datetime',
        ];
    }
}
