<?php

namespace App\Services\Deployment;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CapRoverClient
{
    private ?string $token = null;

    public function attachDomain(string $domain): array
    {
        return $this->post('/api/v2/user/apps/appDefinitions/customdomain', [
            'appName' => $this->appName(),
            'customDomain' => $domain,
        ], ['already has customDomain', 'already exists']);
    }

    public function enableSsl(string $domain): array
    {
        return $this->post('/api/v2/user/apps/appDefinitions/enablecustomdomainssl', [
            'appName' => $this->appName(),
            'customDomain' => $domain,
        ], ['hasSsl', 'already']);
    }

    private function post(string $path, array $payload, array $idempotentPhrases = []): array
    {
        $response = $this->request()->post($this->url($path), $payload);
        $json = $response->json() ?? [];
        $description = (string) ($json['description'] ?? $response->body());

        if ($response->successful() && $this->isSuccess($json)) {
            return [
                'path' => $path,
                'description' => $description ?: 'OK',
            ];
        }

        foreach ($idempotentPhrases as $phrase) {
            if (Str::contains(Str::lower($description), Str::lower($phrase))) {
                return [
                    'path' => $path,
                    'description' => $description,
                    'idempotent' => true,
                ];
            }
        }

        throw new \RuntimeException('CapRover API gagal: '.$description);
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'x-captain-auth' => $this->authToken(),
            'x-namespace' => (string) config('services.arventa_deployment.caprover.namespace', 'captain'),
        ])
            ->acceptJson()
            ->asJson()
            ->timeout(60);
    }

    private function authToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $configuredToken = config('services.arventa_deployment.caprover.auth_token');

        if (filled($configuredToken)) {
            return $this->token = (string) $configuredToken;
        }

        $password = config('services.arventa_deployment.caprover.password');

        if (blank($password)) {
            throw new \RuntimeException('CAPROVER_AUTH_TOKEN atau CAPROVER_PASSWORD wajib diisi.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->url('/api/v2/login'), [
                'password' => $password,
            ])
            ->throw()
            ->json();

        $token = $response['data']['token'] ?? null;

        if (blank($token)) {
            throw new \RuntimeException('Login CapRover berhasil dipanggil, tapi token tidak ditemukan.');
        }

        return $this->token = $token;
    }

    private function url(string $path): string
    {
        $baseUrl = config('services.arventa_deployment.caprover.base_url');

        if (blank($baseUrl)) {
            throw new \RuntimeException('CAPROVER_BASE_URL wajib diisi untuk automation CapRover.');
        }

        return rtrim((string) $baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function appName(): string
    {
        $appName = config('services.arventa_deployment.caprover.app_name');

        if (blank($appName)) {
            throw new \RuntimeException('CAPROVER_APP_NAME wajib diisi.');
        }

        return (string) $appName;
    }

    private function isSuccess(array $json): bool
    {
        if (! array_key_exists('status', $json)) {
            return true;
        }

        return (int) $json['status'] < 1000;
    }
}
