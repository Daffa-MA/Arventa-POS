<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosInstance extends Model
{
    protected $fillable = [
        'store_name',
        'owner_name',
        'owner_phone',
        'subdomain',
        'domain',
        'database_name',
        'admin_username',
        'admin_password',
        'app_package_name',
        'status',
        'provisioned_at',
        'deployment_notes',
    ];

    protected function casts(): array
    {
        return [
            'admin_password' => 'encrypted',
            'provisioned_at' => 'datetime',
        ];
    }
}
