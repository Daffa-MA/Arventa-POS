<?php

namespace Tests\Feature;

use App\Models\CashierPairingCode;
use App\Models\CashierDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierPairingCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_only_expired_unpaired_pairing_codes(): void
    {
        $this->seed();

        CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'code' => '111111',
            'cashier_name' => 'Expired',
            'expires_at' => now()->subMinute(),
        ]);

        CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'code' => '222222',
            'cashier_name' => 'Active',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->withAdminSession()->from('/admin/devices')->delete('/admin/devices/pairing-codes/expired');

        $response->assertRedirect('/admin/devices');
        $this->assertDatabaseMissing('cashier_pairing_codes', ['code' => '111111']);
        $this->assertDatabaseHas('cashier_pairing_codes', ['code' => '222222']);
    }

    public function test_admin_can_cancel_active_unpaired_pairing_code_from_card_action(): void
    {
        $this->seed();

        $pairingCode = CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'code' => '333333',
            'cashier_name' => 'Active',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->withAdminSession()->from('/admin/devices')->delete("/admin/devices/pairing-codes/{$pairingCode->id}");

        $response->assertRedirect('/admin/devices');
        $this->assertDatabaseMissing('cashier_pairing_codes', ['code' => '333333']);
    }

    public function test_devices_page_lists_active_and_paired_pairing_codes_but_cleans_expired_unpaired_codes(): void
    {
        $this->seed();

        CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'code' => '444444',
            'cashier_name' => 'Active',
            'expires_at' => now()->addMinutes(5),
        ]);

        CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'code' => '555555',
            'cashier_name' => 'Expired',
            'expires_at' => now()->subMinute(),
        ]);

        $activeUser = User::query()->create([
            'name' => 'Kasir Aktif',
            'email' => 'kasir-aktif@example.test',
            'username' => 'kasir_aktif',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        CashierDevice::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'user_id' => $activeUser->id,
            'device_name' => 'Active Device',
            'device_uid' => 'active-paired-device',
            'paired_at' => now(),
            'last_seen_at' => now(),
        ]);

        CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'code' => '666666',
            'cashier_name' => 'Paired',
            'expires_at' => now()->addMinutes(5),
            'paired_at' => now(),
            'paired_user_id' => $activeUser->id,
        ]);

        $revokedUser = User::query()->create([
            'name' => 'Kasir Revoked',
            'email' => 'kasir-revoked@example.test',
            'username' => 'kasir_revoked',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        CashierDevice::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'user_id' => $revokedUser->id,
            'device_name' => 'Revoked Device',
            'device_uid' => 'revoked-paired-device',
            'paired_at' => now(),
            'last_seen_at' => now(),
            'revoked_at' => now(),
        ]);

        CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'code' => '777777',
            'cashier_name' => 'Paired Revoked',
            'expires_at' => now()->addMinutes(5),
            'paired_at' => now(),
            'paired_user_id' => $revokedUser->id,
        ]);

        $response = $this->withAdminSession()->get('/admin/devices');

        $response->assertOk();
        $response->assertSee('444444');
        $response->assertDontSee('555555');
        $response->assertSee('666666');
        $response->assertDontSee('777777');
        $this->assertDatabaseMissing('cashier_pairing_codes', ['code' => '555555']);
    }

    public function test_devices_page_hides_revoked_devices(): void
    {
        $this->seed();

        $user = User::query()->create([
            'name' => 'Kasir',
            'email' => 'kasir-device@example.test',
            'username' => 'kasir_device',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        CashierDevice::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'user_id' => $user->id,
            'device_name' => 'Infinix X6833B',
            'device_uid' => 'revoked-device',
            'paired_at' => now(),
            'last_seen_at' => now(),
            'revoked_at' => now(),
        ]);

        CashierDevice::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'user_id' => $user->id,
            'device_name' => 'Pixel Tablet',
            'device_uid' => 'active-device',
            'paired_at' => now(),
            'last_seen_at' => now(),
        ]);

        $response = $this->withAdminSession()->get('/admin/devices');

        $response->assertOk();
        $response->assertDontSee('Infinix X6833B');
        $response->assertSee('Pixel Tablet');
    }
}
