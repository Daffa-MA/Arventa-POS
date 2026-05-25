<?php

namespace Tests\Feature;

use App\Models\PosInstance;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
            'domain' => 'parfume-pos.arventapos.local',
            'database_name' => 'arventa_pos_parfume_pos',
            'package_name' => 'com.arventapos.parfumepos',
            'admin_username' => 'admin_parfume_pos',
            'status' => 'pending',
            'deployment_status' => 'pending',
        ]);

        $instance = PosInstance::query()->where('store_name', 'Parfume POS')->firstOrFail();
        $this->assertNotEmpty($instance->admin_password);

        $admin = User::query()->where('username', 'admin_parfume_pos')->firstOrFail();
        $this->assertTrue(Hash::check($instance->admin_password, $admin->password));
    }

    public function test_developer_can_update_status_and_deploy_pos_instance(): void
    {
        $this->seed();

        $instance = PosInstance::query()->create([
            'store_name' => 'Deploy Store',
            'buyer_name' => 'Buyer',
            'contact' => '08123',
            'subdomain' => 'deploy-store',
            'domain' => 'deploy-store.arventapos.local',
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
    }
}
