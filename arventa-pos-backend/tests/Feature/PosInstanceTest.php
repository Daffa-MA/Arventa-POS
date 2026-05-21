<?php

namespace Tests\Feature;

use App\Models\PosInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosInstanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_can_generate_pos_instance(): void
    {
        $this->seed();

        $response = $this->post('/developer/pos', [
            'store_name' => 'Parfume POS',
            'owner_name' => 'Pemilik Parfume',
        ]);

        $response->assertRedirect('/developer/pos');

        $this->assertDatabaseHas('pos_instances', [
            'store_name' => 'Parfume POS',
            'owner_name' => 'Pemilik Parfume',
            'subdomain' => 'parfume-pos',
            'domain' => 'parfume-pos.arventapos.local',
            'database_name' => 'arventa_pos_parfume_pos',
            'admin_username' => 'admin_parfume_pos',
            'status' => 'draft',
        ]);

        $this->assertNotEmpty(PosInstance::query()->firstOrFail()->admin_password);
    }
}
