<?php

namespace Tests;

use App\Models\PosInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function withAdminSession(?User $user = null): static
    {
        $user ??= User::query()->where('role', 'admin')->firstOrFail();

        return $this->withSession(['arventa_admin_id' => $user->id]);
    }

    protected function defaultPosInstanceId(): int
    {
        return (int) PosInstance::query()->orderBy('id')->value('id');
    }

    protected function withDeveloperSession(?User $user = null): static
    {
        $user ??= User::query()->firstOrCreate([
            'username' => 'developer_test',
        ], [
            'name' => 'Developer Test',
            'email' => 'developer-test@example.test',
            'password' => 'password',
            'role' => 'developer',
            'is_active' => true,
        ]);

        return $this->withSession(['arventa_developer_id' => $user->id]);
    }
}
