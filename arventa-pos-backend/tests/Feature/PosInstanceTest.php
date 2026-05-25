<?php

namespace Tests\Feature;

use App\Models\PosInstance;
use App\Models\User;
use Illuminate\Support\Facades\Config;
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
            'domain' => 'parfume-pos.arventa.my.id',
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
        Config::set('services.arventa_deployment.pos_base_domain', 'arventa.my.id');
        Config::set('services.arventa_deployment.dns.provider', 'cloudflare');
        Config::set('services.arventa_deployment.dns.record_type', 'CNAME');
        Config::set('services.arventa_deployment.dns.record_content', 'arventa.arventa.my.id');
        Config::set('services.arventa_deployment.cloudflare.token', 'test-cloudflare-token');
        Config::set('services.arventa_deployment.cloudflare.zone_id', 'test-zone');
        Config::set('services.arventa_deployment.caprover.enabled', true);
        Config::set('services.arventa_deployment.caprover.base_url', 'https://captain.arventa.my.id');
        Config::set('services.arventa_deployment.caprover.auth_token', 'test-captain-token');
        Config::set('services.arventa_deployment.caprover.app_name', 'arventa');

        Http::fake([
            'https://api.cloudflare.com/client/v4/zones/test-zone/dns_records*' => Http::sequence()
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
            'domain' => 'deploy-store.arventa.my.id',
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

            return $request['name'] === 'deploy-store'
                && $request['type'] === 'CNAME'
                && $request['content'] === 'arventa.arventa.my.id';
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
            'domain' => 'tropizz.arventa.my.id',
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
}
