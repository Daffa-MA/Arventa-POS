<?php

namespace App\Services;

use App\Models\PosInstance;
use App\Services\Deployment\CapRoverClient;
use App\Services\Deployment\CloudflareDnsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PosDeploymentService
{
    public function __construct(
        private readonly CapRoverClient $capRover,
        private readonly CloudflareDnsService $cloudflareDns,
    ) {}

    public function deploy(PosInstance $instance): void
    {
        $domain = $this->normalizeDomain($instance->domain);
        $notes = [];

        Log::info('POS deployment started.', [
            'pos_instance_id' => $instance->id,
            'domain' => $domain,
            'database_name' => $instance->database_name,
        ]);

        if (blank($domain)) {
            throw new \RuntimeException('Domain wajib tersedia sebelum deploy.');
        }

        if (config('services.arventa_deployment.mode') !== 'automatic') {
            $this->saveNotes($instance, [
                'Deploy automation masih mode manual.',
                'Set ARVENTA_DEPLOYMENT_MODE=automatic untuk menjalankan DNS + CapRover otomatis.',
                'Tenant tetap memakai satu database Laravel bersama. Field database_name hanya metadata tenant: '.$instance->database_name,
                'URL admin pembeli: https://'.$domain.'/admin/login',
            ]);

            throw new \RuntimeException('Deploy automation belum aktif. Isi env DNS/CapRover lalu set ARVENTA_DEPLOYMENT_MODE=automatic.');
        }

        if (config('services.arventa_deployment.dns.provider') !== 'cloudflare') {
            throw new \RuntimeException('Automation DNS saat ini membutuhkan ARVENTA_DNS_PROVIDER=cloudflare.');
        }

        if (! config('services.arventa_deployment.caprover.enabled')) {
            throw new \RuntimeException('Automation CapRover belum aktif. Set CAPROVER_AUTOMATION_ENABLED=true.');
        }

        $dns = $this->cloudflareDns->upsert($domain);
        $notes[] = "DNS {$dns['action']}: {$dns['type']} {$dns['name']} -> {$dns['content']}";

        $domainResult = $this->capRover->attachDomain($domain);
        $notes[] = 'CapRover domain attached: '.$domainResult['description'];

        if (config('services.arventa_deployment.caprover.enable_ssl')) {
            $sslResult = $this->capRover->enableSsl($domain);
            $notes[] = 'CapRover SSL enabled: '.$sslResult['description'];
        }

        $notes[] = 'Tenant memakai satu database Laravel bersama; tidak membuat database fisik baru.';
        $notes[] = 'URL admin pembeli: https://'.$domain.'/admin/login';

        $this->saveNotes($instance, $notes);

        Log::info('POS deployment finished.', [
            'pos_instance_id' => $instance->id,
        ]);
    }

    private function normalizeDomain(?string $domain): string
    {
        return Str::of((string) $domain)
            ->lower()
            ->replaceMatches('/^https?:\/\//', '')
            ->replaceMatches('/\/.*$/', '')
            ->trim('.')
            ->toString();
    }

    private function saveNotes(PosInstance $instance, array $notes): void
    {
        $instance->forceFill([
            'deployment_notes' => implode(PHP_EOL, $notes),
        ])->save();
    }
}
