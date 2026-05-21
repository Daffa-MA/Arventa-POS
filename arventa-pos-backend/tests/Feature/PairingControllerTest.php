<?php

namespace Tests\Feature;

use App\Models\CashierDevice;
use App\Models\CashierPairingCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PairingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_revoked_device_can_pair_again_with_same_device_uid(): void
    {
        $this->seed();

        $oldUser = User::query()->create([
            'name' => 'Kasir Lama',
            'email' => 'kasir-lama@example.test',
            'username' => 'kasir_lama',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $device = CashierDevice::query()->create([
            'user_id' => $oldUser->id,
            'device_name' => 'Infinix X6833B',
            'device_uid' => 'INFINIX-Infinix X6833B-ABC123',
            'paired_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
            'revoked_at' => now()->subMinute(),
        ]);

        $pairing = CashierPairingCode::query()->create([
            'code' => '756012',
            'cashier_name' => 'Kasir Baru',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/pairing/connect', [
            'code' => '756012',
            'device_name' => 'Infinix X6833B',
            'device_uid' => 'INFINIX-Infinix X6833B-ABC123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('cashier.name', 'Kasir Baru');
        $response->assertJsonPath('device.id', $device->id);

        $this->assertDatabaseCount('cashier_devices', 1);
        $this->assertDatabaseHas('cashier_devices', [
            'id' => $device->id,
            'device_uid' => 'INFINIX-Infinix X6833B-ABC123',
            'revoked_at' => null,
        ]);
        $this->assertNotNull($pairing->refresh()->paired_at);
    }
}
