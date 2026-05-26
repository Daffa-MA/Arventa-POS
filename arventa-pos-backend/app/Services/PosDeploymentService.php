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

        $this->assertTenantDomain($domain);

        if (config('services.arventa_deployment.mode') !== 'automatic') {
            $this->saveNotes($instance, [
                'Deploy automation masih mode manual.',
                'Set ARVENTA_DEPLOYMENT_MODE=automatic untuk menjalankan DNS + CapRover otomatis.',
                'Tenant tetap memakai satu database Laravel bersama. Field database_name hanya metadata tenant: '.$instance->database_name,
                'URL admin pembeli: https://'.$domain.'/admin/login',
            ]);

            throw new \RuntimeException('Deploy automation belum aktif. Isi env DNS/CapRover lalu set ARVENTA_DEPLOYMENT_MODE=automatic.');
        }

        if (! config('services.arventa_deployment.caprover.enabled')) {
            throw new \RuntimeException('Automation CapRover belum aktif. Set CAPROVER_AUTOMATION_ENABLED=true.');
        }

        $notes[] = $this->prepareDns($domain);

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

    private function prepareDns(string $domain): string
    {
        $provider = Str::of((string) config('services.arventa_deployment.dns.provider'))
            ->lower()
            ->trim()
            ->toString();

        if ($provider === 'wildcard') {
            Log::info('DNS covered by wildcard.', [
                'domain' => $domain,
                'wildcard' => '*.'.$this->baseDomain(),
            ]);

            return 'DNS covered by wildcard: *.'.$this->baseDomain();
        }

        if ($provider === 'cloudflare') {
            $dns = $this->cloudflareDns->upsert($domain);

            return "DNS {$dns['action']}: {$dns['type']} {$dns['name']} -> {$dns['content']}";
        }

        throw new \RuntimeException('ARVENTA_DNS_PROVIDER wajib cloudflare atau wildcard untuk deploy otomatis.');
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

    private function assertTenantDomain(string $domain): void
    {
        $baseDomain = $this->baseDomain();

        if (! Str::endsWith($domain, '.'.$baseDomain)) {
            throw new \RuntimeException("Domain {$domain} tidak valid untuk tenant. Domain harus berakhiran .{$baseDomain}. Jalankan php artisan arventa:repair-pos-domains.");
        }
    }

    private function baseDomain(): string
    {
        $domain = $this->normalizeDomain((string) config('services.arventa_deployment.pos_base_domain'));

        if (blank($domain)) {
            throw new \RuntimeException('ARVENTA_POS_BASE_DOMAIN wajib diisi, contoh: pos.arventa.my.id.');
        }

        return $domain;
    }

    private function saveNotes(PosInstance $instance, array $notes): void
    {
        $instance->forceFill([
            'deployment_notes' => implode(PHP_EOL, $notes),
        ])->save();
    }
}
