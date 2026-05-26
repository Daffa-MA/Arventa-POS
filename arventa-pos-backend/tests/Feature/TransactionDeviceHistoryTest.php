<?php

namespace Tests\Feature;

use App\Models\CashierDevice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDeviceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_created_from_paired_device_stores_device_snapshot(): void
    {
        $this->seed();
        $posInstanceId = $this->defaultPosInstanceId();

        $cashier = User::query()->create([
            'name' => 'Kasir Tablet',
            'email' => 'kasir-tablet@example.test',
            'username' => 'kasir_tablet',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $posInstanceId,
        ]);

        $device = CashierDevice::query()->create([
            'pos_instance_id' => $posInstanceId,
            'user_id' => $cashier->id,
            'device_name' => 'Tablet Depan',
            'device_uid' => 'tablet-depan-001',
            'paired_at' => now(),
        ]);

        $product = Product::query()->create([
            'pos_instance_id' => $posInstanceId,
            'name' => 'Botol',
            'type' => 'product',
            'unit' => 'pcs',
            'price' => 2000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $token = $cashier->createToken('cashier-device-'.$device->id)->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
                'paid_amount' => 5000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('sales', [
            'cashier_id' => $cashier->id,
            'cashier_device_id' => $device->id,
            'cashier_device_name' => 'Tablet Depan',
            'grand_total' => 4440,
        ]);

        $this->assertNotNull($device->refresh()->last_seen_at);
    }

    public function test_admin_transactions_can_be_filtered_by_cashier_device(): void
    {
        $this->seed();
        $posInstanceId = $this->defaultPosInstanceId();

        $cashier = User::query()->create([
            'name' => 'Kasir Shift',
            'email' => 'kasir-shift@example.test',
            'username' => 'kasir_shift',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $posInstanceId,
        ]);

        $frontDevice = CashierDevice::query()->create([
            'pos_instance_id' => $posInstanceId,
            'user_id' => $cashier->id,
            'device_name' => 'Tablet Depan',
            'device_uid' => 'tablet-filter-front',
            'paired_at' => now(),
        ]);

        $backDevice = CashierDevice::query()->create([
            'pos_instance_id' => $posInstanceId,
            'user_id' => $cashier->id,
            'device_name' => 'HP Belakang',
            'device_uid' => 'tablet-filter-back',
            'paired_at' => now(),
        ]);

        Sale::query()->create([
            'pos_instance_id' => $posInstanceId,
            'invoice_number' => 'ARV-FRONT-001',
            'cashier_id' => $cashier->id,
            'cashier_device_id' => $frontDevice->id,
            'cashier_device_name' => $frontDevice->device_name,
            'subtotal' => 10000,
            'tax_total' => 0,
            'service_charge_total' => 0,
            'grand_total' => 10000,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'payment_method' => 'cash',
        ]);

        Sale::query()->create([
            'pos_instance_id' => $posInstanceId,
            'invoice_number' => 'ARV-BACK-001',
            'cashier_id' => $cashier->id,
            'cashier_device_id' => $backDevice->id,
            'cashier_device_name' => $backDevice->device_name,
            'subtotal' => 15000,
            'tax_total' => 0,
            'service_charge_total' => 0,
            'grand_total' => 15000,
            'paid_amount' => 15000,
            'change_amount' => 0,
            'payment_method' => 'cash',
        ]);

        $response = $this->withAdminSession()->get('/admin/transactions?device='.$frontDevice->id);

        $response->assertOk();
        $response->assertSee('ARV-FRONT-001');
        $response->assertSee('Tablet Depan');
        $response->assertDontSee('ARV-BACK-001');
    }
}
