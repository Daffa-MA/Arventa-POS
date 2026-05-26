<?php

namespace Tests\Feature;

use App\Models\PosInstance;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosInstanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_can_generate_pos_instance(): void
    {
        $this->seed();

        $response = $this->withDeveloperSession()->post('/developer/pos', [
            'store_name' => 'Parfume POS',
            'buyer_name' => 'Pemilik Parfume',
            'contact' => '08123456789',
        ]);

        $response->assertRedirect('/developer/pos');

        $this->assertDatabaseHas('pos_instances', [
            'store_name' => 'Parfume POS',
            'buyer_name' => 'Pemilik Parfume',
            'contact' => '08123456789',
            'subdomain' => 'parfume-pos',
            'domain' => 'parfume-pos.pos.arventa.my.id',
            'database_name' => 'arventa_pos_parfume_pos',
            'package_name' => 'com.arventapos.parfumepos',
            'admin_username' => 'admin_parfume_pos',
            'status' => 'pending',
            'deployment_status' => 'pending',
        ]);

        $instance = PosInstance::query()->where('store_name', 'Parfume POS')->firstOrFail();
        $this->assertNotEmpty($instance->admin_password);
        $this->assertDatabaseHas('store_settings', [
            'pos_instance_id' => $instance->id,
            'store_name' => 'Parfume POS',
        ]);

        $admin = User::query()->where('username', 'admin_parfume_pos')->firstOrFail();
        $this->assertTrue(Hash::check($instance->admin_password, $admin->password));
    }

    public function test_developer_can_update_status_and_deploy_pos_instance(): void
    {
        $this->seed();
        Config::set('services.arventa_deployment.mode', 'automatic');
        Config::set('services.arventa_deployment.pos_base_domain', 'pos.arventa.my.id');
        Config::set('services.arventa_deployment.dns.provider', 'cloudflare');
        Config::set('services.arventa_deployment.dns.record_type', 'CNAME');
        Config::set('services.arventa_deployment.dns.record_content', 'arventa.arventa.my.id');
        Config::set('services.arventa_deployment.cloudflare.token', 'test-cloudflare-token');
        Config::set('services.arventa_deployment.cloudflare.zone_id', '0123456789abcdef0123456789abcdef');
        Config::set('services.arventa_deployment.caprover.enabled', true);
        Config::set('services.arventa_deployment.caprover.base_url', 'https://captain.arventa.my.id');
        Config::set('services.arventa_deployment.caprover.auth_token', 'test-captain-token');
        Config::set('services.arventa_deployment.caprover.app_name', 'arventa');

        Http::fake([
            'https://api.cloudflare.com/client/v4/zones/0123456789abcdef0123456789abcdef/dns_records*' => Http::sequence()
                ->push(['success' => true, 'result' => []])
                ->push(['success' => true, 'result' => ['id' => 'record-1']]),
            'https://captain.arventa.my.id/api/v2/user/apps/appDefinitions/customdomain' => Http::response(['status' => 100, 'description' => 'OK']),
            'https://captain.arventa.my.id/api/v2/user/apps/appDefinitions/enablecustomdomainssl' => Http::response(['status' => 100, 'description' => 'OK']),
        ]);

        $instance = PosInstance::query()->create([
            'store_name' => 'Deploy Store',
            'buyer_name' => 'Buyer',
            'contact' => '08123',
            'subdomain' => 'deploy-store',
            'domain' => 'deploy-store.pos.arventa.my.id',
            'database_name' => 'arventa_pos_deploy_store',
            'package_name' => 'com.arventapos.deploystore',
            'admin_username' => 'admin_deploy_store',
            'admin_password' => 'secret-password',
            'admin_password_hash' => Hash::make('secret-password'),
            'status' => 'pending',
            'deployment_status' => 'pending',
        ]);

        $this->withDeveloperSession()
            ->from('/developer/pos')
            ->patch("/developer/pos/{$instance->id}/status", ['status' => 'active'])
            ->assertRedirect('/developer/pos');

        $this->assertDatabaseHas('pos_instances', [
            'id' => $instance->id,
            'status' => 'active',
        ]);

        $this->withDeveloperSession()
            ->from('/developer/pos')
            ->post("/developer/pos/{$instance->id}/deploy")
            ->assertRedirect('/developer/pos');

        $this->assertDatabaseHas('pos_instances', [
            'id' => $instance->id,
            'deployment_status' => 'deployed',
            'deployment_error' => null,
        ]);

        Http::assertSent(function ($request): bool {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), '/dns_records')) {
                return false;
            }

            return $request['name'] === 'deploy-store.pos'
                && $request['type'] === 'CNAME'
                && $request['content'] === 'arventa.arventa.my.id';
        });

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/customdomain')
                && $request['appName'] === 'arventa'
                && $request['customDomain'] === 'deploy-store.pos.arventa.my.id';
        });
    }

    public function test_developer_can_deploy_pos_instance_with_wildcard_dns_without_cloudflare_call(): void
    {
        $this->seed();
        Config::set('services.arventa_deployment.mode', 'automatic');
        Config::set('services.arventa_deployment.pos_base_domain', 'pos.arventa.my.id');
        Config::set('services.arventa_deployment.dns.provider', 'wildcard');
        Config::set('services.arventa_deployment.caprover.enabled', true);
        Config::set('services.arventa_deployment.caprover.base_url', 'https://captain.arventa.my.id');
        Config::set('services.arventa_deployment.caprover.auth_token', 'test-captain-token');
        Config::set('services.arventa_deployment.caprover.app_name', 'arventa');
        Config::set('services.arventa_deployment.caprover.enable_ssl', true);

        Http::fake([
            'https://captain.arventa.my.id/api/v2/user/apps/appDefinitions/customdomain' => Http::response(['status' => 100, 'description' => 'OK']),
            'https://captain.arventa.my.id/api/v2/user/apps/appDefinitions/enablecustomdomainssl' => Http::response(['status' => 100, 'description' => 'OK']),
            'https://api.cloudflare.com/*' => Http::response(['success' => false], 500),
        ]);

        $instance = PosInstance::query()->create([
            'store_name' => 'Wildcard Store',
            'buyer_name' => 'Buyer',
            'contact' => '08123',
            'subdomain' => 'wildcard-store',
            'domain' => 'wildcard-store.pos.arventa.my.id',
            'database_name' => 'arventa_pos_wildcard_store',
            'package_name' => 'com.arventapos.wildcardstore',
            'admin_username' => 'admin_wildcard_store',
            'admin_password' => 'secret-password',
            'admin_password_hash' => Hash::make('secret-password'),
            'status' => 'active',
            'deployment_status' => 'pending',
        ]);

        $this->withDeveloperSession()
            ->from('/developer/pos')
            ->post("/developer/pos/{$instance->id}/deploy")
            ->assertRedirect('/developer/pos');

        $this->assertDatabaseHas('pos_instances', [
            'id' => $instance->id,
            'deployment_status' => 'deployed',
            'deployment_error' => null,
        ]);

        $instance->refresh();
        $this->assertStringContainsString('DNS covered by wildcard: *.pos.arventa.my.id', (string) $instance->deployment_notes);

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'api.cloudflare.com'));

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/customdomain')
                && $request['appName'] === 'arventa'
                && $request['customDomain'] === 'wildcard-store.pos.arventa.my.id';
        });

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/enablecustomdomainssl')
                && $request['customDomain'] === 'wildcard-store.pos.arventa.my.id';
        });
    }

    public function test_developer_domain_request_is_ignored_and_subdomain_domain_is_generated(): void
    {
        $this->seed();

        $this->withDeveloperSession()->post('/developer/pos', [
            'store_name' => 'Tropizz',
            'buyer_name' => 'Iwan',
            'contact' => '08973128675',
            'subdomain' => 'tropizz',
            'domain' => 'tropizz.com',
        ])->assertRedirect('/developer/pos');

        $this->assertDatabaseHas('pos_instances', [
            'store_name' => 'Tropizz',
            'subdomain' => 'tropizz',
            'domain' => 'tropizz.pos.arventa.my.id',
        ]);
    }

    public function test_developer_subdomain_rejects_dots_spaces_uppercase_and_edge_hyphens(): void
    {
        $this->seed();

        foreach (['tropizz.com', 'tro pizz', 'Tropizz', '-tropizz', 'tropizz-'] as $subdomain) {
            $this->withDeveloperSession()->post('/developer/pos', [
                'store_name' => 'Invalid '.$subdomain,
                'buyer_name' => 'Buyer',
                'contact' => '08123',
                'subdomain' => $subdomain,
            ])->assertSessionHasErrors('subdomain');
        }
    }

    public function test_repair_pos_domains_command_recalculates_old_domains(): void
    {
        $this->seed();

        $instance = PosInstance::query()->create([
            'store_name' => 'Tropizz',
            'buyer_name' => 'Iwan',
            'contact' => '08973128675',
            'subdomain' => 'tropizz',
            'domain' => 'tropizz.arventa.my.id',
            'database_name' => 'tropizz',
            'package_name' => 'com.tropizz',
            'admin_username' => 'iwan',
            'admin_password' => 'secret-password',
            'admin_password_hash' => Hash::make('secret-password'),
            'status' => 'active',
            'deployment_status' => 'failed',
            'deployment_error' => 'old domain rejected',
        ]);

        $this->artisan('arventa:repair-pos-domains')
            ->expectsOutput("UPDATED #{$instance->id} Tropizz: tropizz.arventa.my.id -> tropizz.pos.arventa.my.id")
            ->assertExitCode(0);

        $this->assertDatabaseHas('pos_instances', [
            'id' => $instance->id,
            'domain' => 'tropizz.pos.arventa.my.id',
            'deployment_status' => 'pending',
            'deployment_error' => null,
        ]);
    }

    public function test_developer_can_permanently_delete_pos_instance_and_tenant_data(): void
    {
        $this->seed();

        $instance = PosInstance::query()->create([
            'store_name' => 'Legacy Tropizz',
            'buyer_name' => 'Iwan',
            'contact' => '08973128675',
            'subdomain' => 'tropizz',
            'domain' => 'tropizz.com',
            'database_name' => 'tropizz',
            'package_name' => 'com.tropizz',
            'admin_username' => 'iwan',
            'admin_password' => 'secret-password',
            'admin_password_hash' => Hash::make('secret-password'),
            'status' => 'suspended',
            'deployment_status' => 'failed',
        ]);

        $admin = User::query()->create([
            'name' => 'Tropizz Admin',
            'email' => 'iwan@tropizz.test',
            'username' => 'iwan',
            'password' => 'secret-password',
            'role' => 'admin',
            'is_active' => true,
            'pos_instance_id' => $instance->id,
        ]);

        $cashier = User::query()->create([
            'name' => 'Tropizz Cashier',
            'email' => 'cashier@tropizz.test',
            'username' => 'cashier_tropizz',
            'password' => 'secret-password',
            'role' => 'cashier',
            'is_active' => true,
            'pos_instance_id' => $instance->id,
        ]);
        $cashier->createToken('cashier-test-token');

        DB::table('store_settings')->insert([
            'pos_instance_id' => $instance->id,
            'store_name' => 'Legacy Tropizz',
            'business_type' => 'retail',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'pos_instance_id' => $instance->id,
            'name' => 'Legacy Item',
            'type' => 'product',
            'unit' => 'pcs',
            'price' => 1000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleId = DB::table('sales')->insertGetId([
            'pos_instance_id' => $instance->id,
            'invoice_number' => 'TRP-001',
            'cashier_id' => $cashier->id,
            'subtotal' => 1000,
            'tax_total' => 0,
            'service_charge_total' => 0,
            'grand_total' => 1000,
            'paid_amount' => 1000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sale_items')->insert([
            'sale_id' => $saleId,
            'product_id' => $productId,
            'name' => 'Legacy Item',
            'unit_price' => 1000,
            'quantity' => 1,
            'unit' => 'pcs',
            'line_total' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cashier_pairing_codes')->insert([
            'pos_instance_id' => $instance->id,
            'code' => '123456',
            'cashier_name' => 'Tropizz Cashier',
            'expires_at' => now()->addMinutes(10),
            'paired_user_id' => $cashier->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cashier_devices')->insert([
            'pos_instance_id' => $instance->id,
            'user_id' => $cashier->id,
            'device_name' => 'Tablet Tropizz',
            'device_uid' => 'legacy-device',
            'paired_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withDeveloperSession()
            ->from('/developer/pos')
            ->delete("/developer/pos/{$instance->id}")
            ->assertRedirect('/developer/pos');

        $this->assertDatabaseMissing('pos_instances', ['id' => $instance->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
        $this->assertDatabaseMissing('store_settings', ['pos_instance_id' => $instance->id]);
        $this->assertDatabaseMissing('products', ['pos_instance_id' => $instance->id]);
        $this->assertDatabaseMissing('sales', ['pos_instance_id' => $instance->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $saleId]);
        $this->assertDatabaseMissing('cashier_pairing_codes', ['pos_instance_id' => $instance->id]);
        $this->assertDatabaseMissing('cashier_devices', ['pos_instance_id' => $instance->id]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $cashier->id,
        ]);
    }
}
