<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\PosInstance;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PosInstanceController extends Controller
{
    public function index(): View
    {
        $instances = PosInstance::query()->latest()->get();

        return view('developer.pos-instances.index', [
            'setting' => StoreSetting::query()->firstOrFail(),
            'instances' => $instances,
            'stats' => [
                'total' => $instances->count(),
                'active' => $instances->where('status', 'active')->count(),
                'draft' => $instances->where('status', 'draft')->count(),
                'provisioning' => $instances->where('status', 'provisioning')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'subdomain' => ['nullable', 'alpha_dash:ascii', 'max:60', Rule::unique('pos_instances', 'subdomain')],
            'domain' => ['nullable', 'string', 'max:180', Rule::unique('pos_instances', 'domain')],
            'database_name' => ['nullable', 'alpha_dash:ascii', 'max:80', Rule::unique('pos_instances', 'database_name')],
            'admin_username' => ['nullable', 'alpha_dash:ascii', 'max:60'],
            'admin_password' => ['nullable', 'string', 'min:8', 'max:80'],
            'app_package_name' => ['nullable', 'string', 'max:120'],
        ]);

        $subdomain = $this->uniqueSubdomain($data['subdomain'] ?? Str::slug($data['store_name']));
        $databaseName = $this->uniqueDatabaseName($data['database_name'] ?? 'arventa_pos_'.str_replace('-', '_', $subdomain));
        $domain = $data['domain'] ?? $subdomain.'.arventapos.local';

        $instance = PosInstance::query()->create([
            'store_name' => $data['store_name'],
            'owner_name' => $data['owner_name'] ?? null,
            'owner_phone' => $data['owner_phone'] ?? null,
            'subdomain' => $subdomain,
            'domain' => $domain,
            'database_name' => $databaseName,
            'admin_username' => $data['admin_username'] ?? 'admin_'.str_replace('-', '_', $subdomain),
            'admin_password' => $data['admin_password'] ?? 'Arv-'.Str::upper(Str::random(10)),
            'app_package_name' => $data['app_package_name'] ?? 'com.arventapos.'.str_replace('-', '', $subdomain),
            'status' => 'draft',
            'deployment_notes' => $this->deploymentNotes($domain, $databaseName),
        ]);

        return redirect()
            ->route('developer.pos.index')
            ->with('status', 'POS '.$instance->store_name.' berhasil digenerate sebagai draft.');
    }

    public function updateStatus(Request $request, PosInstance $posInstance): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,provisioning,active,suspended'],
        ]);

        $posInstance->update([
            'status' => $data['status'],
            'provisioned_at' => $data['status'] === 'active' ? now() : $posInstance->provisioned_at,
        ]);

        return back()->with('status', 'Status POS berhasil diperbarui.');
    }

    private function uniqueSubdomain(string $base): string
    {
        $base = Str::slug($base) ?: 'arventa-store';
        $subdomain = $base;
        $suffix = 2;

        while (PosInstance::query()->where('subdomain', $subdomain)->exists()) {
            $subdomain = $base.'-'.$suffix;
            $suffix++;
        }

        return $subdomain;
    }

    private function uniqueDatabaseName(string $base): string
    {
        $base = Str::of($base)->lower()->replace('-', '_')->replaceMatches('/[^a-z0-9_]/', '')->trim('_')->toString() ?: 'arventa_pos_store';
        $databaseName = $base;
        $suffix = 2;

        while (PosInstance::query()->where('database_name', $databaseName)->exists()) {
            $databaseName = $base.'_'.$suffix;
            $suffix++;
        }

        return $databaseName;
    }

    private function deploymentNotes(string $domain, string $databaseName): string
    {
        return implode(PHP_EOL, [
            '1. Pointing domain '.$domain.' ke hosting/VPS pembeli.',
            '2. Buat database MySQL: '.$databaseName,
            '3. Copy source arventa-pos-backend ke folder domain.',
            '4. Set APP_URL=https://'.$domain.' dan DB_DATABASE='.$databaseName.' di .env.',
            '5. Jalankan composer install --no-dev --optimize-autoloader.',
            '6. Jalankan php artisan key:generate, php artisan migrate --seed, php artisan storage:link.',
            '7. Build frontend dengan npm ci lalu npm run build.',
            '8. Kirim URL admin dan pairing QR ke pembeli untuk app kasir.',
        ]);
    }
}
