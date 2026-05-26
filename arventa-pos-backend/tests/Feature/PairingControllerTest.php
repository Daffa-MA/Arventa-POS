<?php

namespace Tests\Feature;

use App\Models\CashierDevice;
use App\Models\CashierPairingCode;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        $device = CashierDevice::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'user_id' => $oldUser->id,
            'device_name' => 'Infinix X6833B',
            'device_uid' => 'INFINIX-Infinix X6833B-ABC123',
            'paired_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
            'revoked_at' => now()->subMinute(),
        ]);

        $pairing = CashierPairingCode::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
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

    public function test_sync_returns_receipt_template_settings(): void
    {
        $this->seed();

        $cashier = User::query()->create([
            'name' => 'Kasir',
            'email' => 'kasir-sync@example.test',
            'username' => 'kasir_sync',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        StoreSetting::query()
            ->where('pos_instance_id', $this->defaultPosInstanceId())
            ->update([
                'receipt_template' => 'detailed',
                'receipt_paper_size' => '80',
                'receipt_show_business_type' => false,
                'receipt_show_payment_method' => true,
                'receipt_show_item_price' => true,
            ]);

        Sanctum::actingAs($cashier);

        $response = $this->getJson('/api/sync');

        $response->assertOk();
        $response->assertJsonPath('store.receipt_template', 'detailed');
        $response->assertJsonPath('store.receipt_paper_size', '80');
        $response->assertJsonPath('store.receipt_show_business_type', false);
        $response->assertJsonPath('store.receipt_show_payment_method', true);
        $response->assertJsonPath('store.receipt_show_item_price', true);
    }
}
