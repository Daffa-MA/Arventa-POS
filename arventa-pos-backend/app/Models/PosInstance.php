<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function setting(): HasOne
    {
        return $this->hasOne(StoreSetting::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
