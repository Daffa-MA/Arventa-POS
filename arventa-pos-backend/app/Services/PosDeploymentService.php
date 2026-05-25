<?php

namespace App\Services;

use App\Models\PosInstance;
use Illuminate\Support\Facades\Log;

class PosDeploymentService
{
    public function deploy(PosInstance $instance): void
    {
        Log::info('POS deployment placeholder started.', [
            'pos_instance_id' => $instance->id,
            'domain' => $instance->domain,
            'database_name' => $instance->database_name,
        ]);

        if (blank($instance->domain) || blank($instance->database_name)) {
            throw new \RuntimeException('Domain dan database wajib tersedia sebelum deploy.');
        }

        // Placeholder only: DNS, database creation, and CapRover provisioning will be added here.
        Log::info('POS deployment placeholder finished.', [
            'pos_instance_id' => $instance->id,
        ]);
    }
}
