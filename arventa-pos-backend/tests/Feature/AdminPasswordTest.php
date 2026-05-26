<?php

namespace Tests\Feature;

use App\Models\PosInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_own_password(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $instance = PosInstance::query()->firstOrFail();
        $instance->forceFill(['admin_username' => $admin->username])->save();

        $response = $this->withAdminSession($admin)->put('/admin/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Password admin berhasil diperbarui.');

        $admin->refresh();
        $instance->refresh();

        $this->assertTrue(Hash::check('new-password-123', $admin->password));
        $this->assertSame('new-password-123', $instance->admin_password);
        $this->assertTrue(Hash::check('new-password-123', $instance->admin_password_hash));
    }

    public function test_admin_password_update_requires_current_password(): void
    {
        $this->seed();

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $response = $this->withAdminSession($admin)->put('/admin/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }
}
