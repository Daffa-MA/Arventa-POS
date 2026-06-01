<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\PosInstance;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\PosDeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeveloperPosController extends Controller
{
    public function index(): View
    {
        $instances = PosInstance::query()->latest()->get();
        $baseDomain = $this->posBaseDomain();

        return view('developer.pos-instances.index', [
            'setting' => $this->developerSetting(),
            'instances' => $instances,
            'baseDomain' => $baseDomain,
            'stats' => [
                'total' => $instances->count(),
                'active' => $instances->where('status', 'active')->count(),
                'pending' => $instances->where('status', 'pending')->count(),
                'deploying' => $instances->where('deployment_status', 'deploying')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'buyer_name' => ['required', 'string', 'max:120'],
            'contact' => ['required', 'string', 'max:40'],
            'subdomain' => ['nullable', 'string', 'max:60', 'regex:/^(?!-)[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pos_instances', 'subdomain')],
            'database_name' => ['nullable', 'alpha_dash:ascii', 'max:80', Rule::unique('pos_instances', 'database_name')],
            'package_name' => ['nullable', 'regex:/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', 'max:160', Rule::unique('pos_instances', 'package_name')],
            'admin_username' => ['nullable', 'alpha_dash:ascii', 'max:60', Rule::unique('users', 'username')],
            'admin_password' => ['nullable', 'string', 'min:8', 'max:120'],
        ]);

        $subdomain = $this->uniqueSubdomain($data['subdomain'] ?? Str::slug($data['store_name']));
        $domain = $this->domainForSubdomain($subdomain);
        $databaseName = $this->uniqueDatabaseName($data['database_name'] ?? 'arventa_pos_'.str_replace('-', '_', $subdomain));
        $packageName = $this->uniquePackageName($data['package_name'] ?? 'com.arventapos.'.str_replace('-', '', $subdomain));
        $adminUsername = $this->uniqueAdminUsername($data['admin_username'] ?? 'admin_'.str_replace('-', '_', $subdomain));
        $plainPassword = ($data['admin_password'] ?? null) ?: 'Arv-'.Str::password(12, true, true, false, false);

        $instance = DB::transaction(function () use ($data, $subdomain, $domain, $databaseName, $packageName, $adminUsername, $plainPassword): PosInstance {
            $instance = PosInstance::query()->create([
                'store_name' => $data['store_name'],
                'buyer_name' => $data['buyer_name'],
                'contact' => $data['contact'],
                'owner_name' => $data['buyer_name'],
                'owner_phone' => $data['contact'],
                'subdomain' => $subdomain,
                'domain' => $domain,
                'database_name' => $databaseName,
                'package_name' => $packageName,
                'app_package_name' => $packageName,
                'admin_username' => $adminUsername,
                'admin_password' => $plainPassword,
                'admin_password_hash' => Hash::make($plainPassword),
                'status' => 'pending',
                'deployment_status' => 'pending',
                'deployment_notes' => $this->deploymentNotes($domain, $databaseName, $packageName),
            ]);

            $adminEmailDomain = Str::of($domain)->replaceMatches('/^https?:\/\//', '')->replace('/', '')->toString();

            User::query()->create([
                'name' => $data['store_name'].' Admin',
                'email' => $adminUsername.'@'.$adminEmailDomain,
                'username' => $adminUsername,
                'password' => $plainPassword,
                'role' => 'admin',
                'is_active' => true,
                'pos_instance_id' => $instance->id,
            ]);

            StoreSetting::query()->create([
                'pos_instance_id' => $instance->id,
                'store_name' => $data['store_name'],
                'business_type' => 'retail',
                'admin_brand_name' => $data['store_name'],
                'admin_console_label' => 'Admin Console',
                'theme_color' => '#2563EB',
                'app_text_color' => '#0F172A',
                'app_secondary_text_color' => '#64748B',
                'app_price_text_color' => '#0F172A',
                'admin_theme_color' => '#0F172A',
                'admin_sidebar_style' => 'light',
                'admin_density' => 'comfortable',
                'app_layout' => 'grid',
                'product_card_style' => 'minimal',
                'pos_orientation' => 'portrait',
                'show_sku_on_app' => false,
                'show_stock_on_app' => true,
                'show_search_on_app' => true,
                'show_cart_on_app' => true,
                'cart_position' => 'bottom',
                'checkout_position' => 'bottom',
                'show_order_summary_on_app' => true,
                'receipt_footer' => 'Terima kasih.',
                'receipt_header_title' => null,
                'receipt_header_subtitle' => null,
                'receipt_header_notes' => null,
                'receipt_header_alignment' => 'center',
                'receipt_show_store_name' => true,
                'receipt_template' => 'classic',
                'receipt_paper_size' => '58',
                'receipt_show_logo' => false,
                'receipt_show_address' => true,
                'receipt_show_datetime' => true,
                'receipt_show_qris' => false,
                'receipt_show_business_type' => true,
                'receipt_show_payment_method' => true,
                'receipt_show_item_price' => true,
                'tax_rate' => 11,
                'service_charge_rate' => 0,
                'currency' => 'IDR',
            ]);

            return $instance;
        });

        if ($request->wantsJson()) {
            return response()->json(['message' => 'POS berhasil digenerate.', 'instance' => $this->instancePayload($instance->fresh())], 201);
        }

        return redirect()
            ->route('developer.pos.index')
            ->with('status', 'POS '.$instance->store_name.' berhasil digenerate.');
    }

    public function updateStatus(Request $request, PosInstance $posInstance): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive,pending,suspended'],
        ]);

        $posInstance->update([
            'status' => $data['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Status POS diperbarui.', 'instance' => $this->instancePayload($posInstance->fresh())]);
        }

        return back()->with('status', 'Status POS berhasil diperbarui.');
    }

    public function deploy(Request $request, PosInstance $posInstance, PosDeploymentService $deploymentService): RedirectResponse|JsonResponse
    {
        $posInstance->update([
            'deployment_status' => 'deploying',
            'deployment_error' => null,
        ]);

        try {
            $deploymentService->deploy($posInstance->fresh());

            $posInstance->update([
                'deployment_status' => 'deployed',
                'deployment_error' => null,
                'deployed_at' => now(),
            ]);

            $message = 'Deploy POS berhasil ditandai deployed.';
        } catch (\Throwable $error) {
            Log::error('POS deployment failed.', [
                'pos_instance_id' => $posInstance->id,
                'message' => $error->getMessage(),
            ]);

            $posInstance->update([
                'deployment_status' => 'failed',
                'deployment_error' => $error->getMessage(),
            ]);

            $message = 'Deploy POS gagal: '.$error->getMessage();
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'instance' => $this->instancePayload($posInstance->fresh())]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, PosInstance $posInstance): RedirectResponse|JsonResponse
    {
        $storeName = $posInstance->store_name;

        DB::transaction(function () use ($posInstance): void {
            $userIds = User::query()
                ->where('pos_instance_id', $posInstance->id)
                ->pluck('id');

            if ($userIds->isNotEmpty() && Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();
            }

            foreach (['cashier_devices', 'cashier_pairing_codes', 'sales', 'products', 'store_settings'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'pos_instance_id')) {
                    DB::table($table)->where('pos_instance_id', $posInstance->id)->delete();
                }
            }

            User::query()
                ->where('pos_instance_id', $posInstance->id)
                ->delete();

            $posInstance->delete();
        });

        $message = 'POS '.$storeName.' sudah dihapus permanen.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    private function uniqueSubdomain(string $base): string
    {
        return $this->uniqueValue('subdomain', Str::slug($base) ?: 'arventa-store', '-');
    }

    private function uniqueDatabaseName(string $base): string
    {
        $base = Str::of($base)->lower()->replace('-', '_')->replaceMatches('/[^a-z0-9_]/', '')->trim('_')->toString() ?: 'arventa_pos_store';

        return $this->uniqueValue('database_name', $base, '_');
    }

    private function uniquePackageName(string $base): string
    {
        $base = Str::of($base)->lower()->replaceMatches('/[^a-z0-9_.]/', '')->trim('.')->toString() ?: 'com.arventapos.store';
        $packageName = $base;
        $suffix = 2;

        while (PosInstance::query()->where('package_name', $packageName)->exists()) {
            $packageName = $base.$suffix;
            $suffix++;
        }

        return $packageName;
    }

    private function uniqueAdminUsername(string $base): string
    {
        $base = Str::of($base)->lower()->replace('-', '_')->replaceMatches('/[^a-z0-9_]/', '')->trim('_')->toString() ?: 'admin_store';
        $username = $base;
        $suffix = 2;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'_'.$suffix;
            $suffix++;
        }

        return $username;
    }

    private function uniqueValue(string $column, string $base, string $separator): string
    {
        $value = $base;
        $suffix = 2;

        while (PosInstance::query()->where($column, $value)->exists()) {
            $value = $base.$separator.$suffix;
            $suffix++;
        }

        return $value;
    }

    private function deploymentNotes(string $domain, string $databaseName, string $packageName): string
    {
        return implode(PHP_EOL, [
            'POS tenant tersimpan dan siap diarahkan ke satu aplikasi Laravel.',
            'Domain: '.$domain,
            'Database tenant key: '.$databaseName,
            'Package Android: '.$packageName,
            'Deploy akan memakai DNS sesuai ARVENTA_DNS_PROVIDER, attach domain ke app CapRover, dan enable SSL.',
            'Tidak membuat database fisik baru karena Arventa memakai satu database multi-tenant dengan pos_instance_id.',
            'URL admin pembeli: https://'.$domain.'/admin/login',
        ]);
    }

    private function posBaseDomain(): string
    {
        $domain = Str::of((string) config('services.arventa_deployment.pos_base_domain'))
            ->lower()
            ->replaceMatches('/^https?:\/\//', '')
            ->replaceMatches('/\/.*$/', '')
            ->trim('.')
            ->toString();

        if (blank($domain)) {
            throw new \RuntimeException('ARVENTA_POS_BASE_DOMAIN wajib diisi, contoh: arventa.my.id.');
        }

        return $domain;
    }

    private function developerSetting(): StoreSetting
    {
        return StoreSetting::query()->first() ?? new StoreSetting([
            'store_name' => 'Arventa POS',
            'business_type' => 'Developer Console',
            'admin_brand_name' => 'Arventa Developer',
            'admin_console_label' => 'Vendor Console',
            'admin_theme_color' => '#0F172A',
            'admin_sidebar_style' => 'light',
            'admin_density' => 'comfortable',
            'theme_color' => '#2563EB',
            'currency' => 'IDR',
        ]);
    }

    private function domainForSubdomain(string $subdomain): string
    {
        return $this->normalizeDomain($subdomain.'.'.$this->posBaseDomain());
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

    private function instancePayload(PosInstance $instance): array
    {
        return $instance->only([
            'id',
            'store_name',
            'buyer_name',
            'contact',
            'subdomain',
            'domain',
            'database_name',
            'package_name',
            'admin_username',
            'status',
            'deployment_status',
            'deployment_error',
            'deployed_at',
            'created_at',
        ]);
    }
}
