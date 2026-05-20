<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'setting' => StoreSetting::query()->firstOrFail(),
            'products' => Product::query()->latest()->get(),
            'sales' => Sale::query()->with('items')->latest()->limit(8)->get(),
        ]);
    }

    public function settings(): View
    {
        return view('admin.settings', [
            'setting' => StoreSetting::query()->firstOrFail(),
        ]);
    }

    public function products(): View
    {
        return view('admin.products', [
            'setting' => StoreSetting::query()->firstOrFail(),
            'products' => Product::query()->latest()->get(),
        ]);
    }

    public function appPreview(): View
    {
        return view('admin.app-preview', [
            'setting' => StoreSetting::query()->firstOrFail(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function transactions(): View
    {
        return view('admin.transactions', [
            'setting' => StoreSetting::query()->firstOrFail(),
            'sales' => Sale::query()->with('items')->latest()->paginate(15),
        ]);
    }

    public function updateSetting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'business_type' => ['required', 'string', 'max:60'],
            'theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_sidebar_style' => ['required', 'in:light,dark,accent'],
            'admin_density' => ['required', 'in:comfortable,compact'],
            'app_layout' => ['required', 'in:grid,list,compact'],
            'product_card_style' => ['required', 'in:image,minimal'],
            'show_sku_on_app' => ['sometimes', 'boolean'],
            'show_stock_on_app' => ['sometimes', 'boolean'],
            'address' => ['nullable', 'string', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:140'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'max:8'],
        ]) + [
            'show_sku_on_app' => $request->boolean('show_sku_on_app'),
            'show_stock_on_app' => $request->boolean('show_stock_on_app'),
        ];

        StoreSetting::query()->firstOrFail()->update($data);

        return back()->with('status', 'Setting toko berhasil diperbarui.');
    }
}
