<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudflareDnsService
{
    public function upsert(string $domain): array
    {
        $token = config('services.arventa_deployment.cloudflare.token');
        $zoneId = config('services.arventa_deployment.cloudflare.zone_id');
        $type = Str::upper((string) config('services.arventa_deployment.dns.record_type', 'CNAME'));
        $content = (string) config('services.arventa_deployment.dns.record_content');
        $recordName = $this->recordName($domain);

        if (blank($token) || blank($zoneId)) {
            throw new \RuntimeException('Cloudflare API token dan zone id wajib diisi untuk automation DNS.');
        }

        if (blank($content)) {
            throw new \RuntimeException('Target DNS wajib diisi melalui ARVENTA_DNS_RECORD_CONTENT.');
        }

        $payload = [
            'type' => $type,
            'name' => $recordName,
            'content' => $this->normalizeContent($type, $content),
            'ttl' => (int) config('services.arventa_deployment.dns.ttl', 1),
            'proxied' => (bool) config('services.arventa_deployment.dns.proxied', false),
        ];

        $existing = $this->request()
            ->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", [
                'type' => $type,
                'name' => $recordName,
            ])
            ->throw()
            ->json('result.0');

        $response = $existing
            ? $this->request()->put("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/{$existing['id']}", $payload)
            : $this->request()->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", $payload);

        $json = $response->throw()->json();

        if (! ($json['success'] ?? false)) {
            throw new \RuntimeException('Cloudflare DNS gagal: '.json_encode($json['errors'] ?? $json));
        }

        return [
            'provider' => 'cloudflare',
            'action' => $existing ? 'updated' : 'created',
            'type' => $payload['type'],
            'name' => $payload['name'],
            'fqdn' => $domain,
            'content' => $payload['content'],
            'proxied' => $payload['proxied'],
        ];
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken((string) config('services.arventa_deployment.cloudflare.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function normalizeContent(string $type, string $content): string
    {
        $content = trim($content);

        if ($type === 'CNAME') {
            return Str::of($content)
                ->replaceMatches('/^https?:\/\//', '')
                ->replaceMatches('/\/.*$/', '')
                ->trim('.')
                ->toString();
        }

        return $content;
    }

    private function recordName(string $domain): string
    {
        $domain = $this->normalizeDomain($domain);
        $baseDomain = $this->baseDomain();
        $suffix = '.'.$baseDomain;

        if (! Str::endsWith($domain, $suffix)) {
            throw new \RuntimeException("Domain {$domain} bukan subdomain dari {$baseDomain}. Automation hanya membuat DNS untuk tenant *.{$baseDomain}.");
        }

        return Str::beforeLast($domain, $suffix);
    }

    private function baseDomain(): string
    {
        return $this->normalizeDomain((string) config('services.arventa_deployment.pos_base_domain', 'arventa.my.id'));
    }

    private function normalizeDomain(string $domain): string
    {
        return Str::of($domain)
            ->lower()
            ->replaceMatches('/^https?:\/\//', '')
            ->replaceMatches('/\/.*$/', '')
            ->trim('.')
            ->toString();
    }
}
