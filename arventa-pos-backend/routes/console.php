<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\PosInstance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('arventa:repair-pos-domains', function (): int {
    $baseDomain = Str::of((string) config('services.arventa_deployment.pos_base_domain'))
        ->lower()
        ->replaceMatches('/^https?:\/\//', '')
        ->replaceMatches('/\/.*$/', '')
        ->trim('.')
        ->toString();

    if (blank($baseDomain)) {
        $this->error('ARVENTA_POS_BASE_DOMAIN wajib diisi, contoh: arventa.my.id.');

        return 1;
    }

    $changed = 0;
    $skipped = 0;

    PosInstance::query()
        ->orderBy('id')
        ->each(function (PosInstance $instance) use ($baseDomain, &$changed, &$skipped): void {
            if (blank($instance->subdomain)) {
                $skipped++;
                $this->warn("SKIP #{$instance->id} {$instance->store_name}: subdomain kosong.");

                return;
            }

            $oldDomain = (string) $instance->domain;
            $newDomain = Str::of($instance->subdomain.'.'.$baseDomain)
                ->lower()
                ->replaceMatches('/^https?:\/\//', '')
                ->replaceMatches('/\/.*$/', '')
                ->trim('.')
                ->toString();
            $normalizedOldDomain = Str::of($oldDomain)
                ->lower()
                ->replaceMatches('/^https?:\/\//', '')
                ->replaceMatches('/\/.*$/', '')
                ->trim('.')
                ->toString();

            $domainChanged = $normalizedOldDomain !== $newDomain;

            if (! $domainChanged && blank($instance->deployment_error)) {
                return;
            }

            $instance->forceFill([
                'domain' => $newDomain,
                'deployment_status' => $domainChanged ? 'pending' : $instance->deployment_status,
                'deployment_error' => null,
                'deployment_notes' => trim((string) $instance->deployment_notes.PHP_EOL.'Domain repaired: '.$oldDomain.' -> '.$newDomain),
            ])->save();

            $changed++;
            $this->line("UPDATED #{$instance->id} {$instance->store_name}: {$oldDomain} -> {$newDomain}");
        });

    $this->info("Repair selesai. Updated: {$changed}. Skipped: {$skipped}.");

    return 0;
})->purpose('Repair POS instance domains from ARVENTA_POS_BASE_DOMAIN');

Artisan::command('arventa:deployment-debug', function (): int {
    $values = [
        'APP_URL' => config('app.url'),
        'CAPROVER_BASE_URL' => config('services.arventa_deployment.caprover.base_url'),
        'ARVENTA_POS_BASE_DOMAIN' => config('services.arventa_deployment.pos_base_domain'),
        'ARVENTA_DNS_PROVIDER' => config('services.arventa_deployment.dns.provider'),
    ];

    Log::info('Arventa deployment debug.', $values);

    foreach ($values as $key => $value) {
        $this->line($key.'='.(blank($value) ? '(empty)' : $value));
    }

    return 0;
})->purpose('Show Arventa deployment URL and DNS configuration');
