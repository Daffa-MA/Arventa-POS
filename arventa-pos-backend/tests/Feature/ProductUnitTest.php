<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_product_with_ml_unit(): void
    {
        $this->seed();

        $response = $this->withAdminSession()->from('/admin/products')->post('/admin/products', [
            'name' => 'Susu Botol',
            'sku' => 'SUSU-ML-001',
            'type' => 'product',
            'unit' => 'ml',
            'price' => 100,
            'stock' => 500.5,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'name' => 'Susu Botol',
            'unit' => 'ml',
            'stock' => 500.5,
        ]);
    }

    public function test_transaction_can_use_decimal_ml_quantity_and_reduce_stock(): void
    {
        $this->seed();

        $cashier = User::query()->create([
            'name' => 'Kasir ML',
            'email' => 'kasir-ml@example.test',
            'username' => 'kasir_ml',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        $product = Product::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'name' => 'Susu Botol',
            'type' => 'product',
            'unit' => 'ml',
            'price' => 100,
            'stock' => 500.5,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($cashier, 'sanctum')
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 125.5],
                ],
                'paid_amount' => 20000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit' => 'ml',
            'quantity' => 125.5,
        ]);
        $this->assertEquals(375.0, (float) $product->refresh()->stock);
    }

    public function test_admin_can_update_existing_product_unit_to_ml(): void
    {
        $this->seed();

        $product = Product::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'name' => 'Air Mineral',
            'type' => 'product',
            'unit' => 'pcs',
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->withAdminSession()->from('/admin/products')->put("/admin/products/{$product->id}", [
            'name' => 'Air Mineral',
            'sku' => null,
            'type' => 'product',
            'unit' => 'ml',
            'price' => 1000,
            'stock' => 10000,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'unit' => 'ml',
            'stock' => 10000,
        ]);
    }

    public function test_admin_can_store_discount_catalog_item(): void
    {
        $this->seed();

        $response = $this->withAdminSession()->from('/admin/products')->post('/admin/products', [
            'name' => 'Diskon Member',
            'sku' => 'DISC-MEMBER',
            'type' => 'product',
            'pricing_rule' => 'discount',
            'unit' => 'pcs',
            'price' => 5000,
            'stock' => 10,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', [
            'name' => 'Diskon Member',
            'type' => 'discount',
            'unit' => 'trx',
            'price' => -5000,
            'stock' => null,
        ]);
    }

    public function test_transaction_can_apply_catalog_discount_item(): void
    {
        $this->seed();

        $cashier = User::query()->create([
            'name' => 'Kasir Diskon',
            'email' => 'kasir-diskon@example.test',
            'username' => 'kasir_diskon',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        $product = Product::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'name' => 'Kopi',
            'type' => 'product',
            'unit' => 'pcs',
            'price' => 20000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $discount = Product::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'name' => 'Diskon Member',
            'type' => 'discount',
            'unit' => 'trx',
            'price' => -5000,
            'stock' => null,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($cashier, 'sanctum')
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                    ['product_id' => $discount->id, 'quantity' => 1],
                ],
                'paid_amount' => 20000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated()
            ->assertJsonPath('sale.subtotal', '15000.00')
            ->assertJsonPath('sale.grand_total', '16650.00');

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $discount->id,
            'unit' => 'trx',
            'unit_price' => -5000,
            'line_total' => -5000,
        ]);
    }

    public function test_admin_can_store_free_quantity_rule(): void
    {
        $this->seed();

        $response = $this->withAdminSession()->from('/admin/products')->post('/admin/products', [
            'name' => 'Alkohol Parfum',
            'sku' => 'ALK-ML',
            'type' => 'product',
            'unit' => 'ml',
            'price' => 100,
            'stock' => 1000,
            'free_quantity' => 100,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', [
            'name' => 'Alkohol Parfum',
            'unit' => 'ml',
            'free_quantity' => 100,
        ]);
    }

    public function test_transaction_charges_full_quantity_once_above_free_quantity(): void
    {
        $this->seed();

        $cashier = User::query()->create([
            'name' => 'Kasir Parfum',
            'email' => 'kasir-parfum@example.test',
            'username' => 'kasir_parfum',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        $product = Product::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'name' => 'Alkohol Parfum',
            'type' => 'product',
            'unit' => 'ml',
            'price' => 100,
            'stock' => 1000,
            'free_quantity' => 100,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($cashier, 'sanctum')
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 150],
                ],
                'paid_amount' => 20000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated()
            ->assertJsonPath('sale.subtotal', '15000.00')
            ->assertJsonPath('sale.grand_total', '16650.00');

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit' => 'ml',
            'unit_price' => 100,
            'quantity' => 150,
            'line_total' => 15000,
        ]);
        $this->assertEquals(850.0, (float) $product->refresh()->stock);
    }

    public function test_transaction_keeps_line_free_at_or_below_free_quantity(): void
    {
        $this->seed();

        $cashier = User::query()->create([
            'name' => 'Kasir Free',
            'email' => 'kasir-free@example.test',
            'username' => 'kasir_free',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $this->defaultPosInstanceId(),
        ]);

        $product = Product::query()->create([
            'pos_instance_id' => $this->defaultPosInstanceId(),
            'name' => 'Alkohol Parfum',
            'type' => 'product',
            'unit' => 'ml',
            'price' => 100,
            'stock' => 1000,
            'free_quantity' => 100,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($cashier, 'sanctum')
            ->postJson('/api/transactions', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 100],
                ],
                'paid_amount' => 0,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated()
            ->assertJsonPath('sale.subtotal', '0.00')
            ->assertJsonPath('sale.grand_total', '0.00');

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 100,
            'line_total' => 0,
        ]);
    }
}
