<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'admin_brand_name' => ['required', 'string', 'max:80'],
            'admin_console_label' => ['required', 'string', 'max:80'],
            'admin_theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_sidebar_style' => ['required', 'in:light,dark,accent'],
            'admin_density' => ['required', 'in:comfortable,compact'],
            'address' => ['nullable', 'string', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:140'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'max:8'],
        ]);

        unset($data['logo']);
        unset($data['qris_image']);

        $setting = StoreSetting::query()->firstOrFail();

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('store-logos', 'public');
        }

        if ($request->hasFile('qris_image')) {
            if ($setting->qris_image_path) {
                Storage::disk('public')->delete($setting->qris_image_path);
            }

            $data['qris_image_path'] = $request->file('qris_image')->store('payment-qris', 'public');
        }

        $setting->update($data);

        return back()->with('status', 'Setting toko berhasil diperbarui.');
    }

    public function updateAppPreview(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'app_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'app_secondary_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'app_price_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'app_layout' => ['required', 'in:grid,list,compact'],
            'product_card_style' => ['required', 'in:image,minimal'],
            'pos_orientation' => ['required', 'in:portrait,landscape'],
            'show_sku_on_app' => ['sometimes', 'boolean'],
            'show_stock_on_app' => ['sometimes', 'boolean'],
            'show_search_on_app' => ['sometimes', 'boolean'],
            'show_cart_on_app' => ['sometimes', 'boolean'],
            'cart_position' => ['required', 'in:bottom,right'],
            'checkout_position' => ['required', 'in:bottom,floating,cart'],
            'show_order_summary_on_app' => ['sometimes', 'boolean'],
        ]) + [
            'show_sku_on_app' => $request->boolean('show_sku_on_app'),
            'show_stock_on_app' => $request->boolean('show_stock_on_app'),
            'show_search_on_app' => $request->boolean('show_search_on_app'),
            'show_cart_on_app' => $request->boolean('show_cart_on_app'),
            'show_order_summary_on_app' => $request->boolean('show_order_summary_on_app'),
        ];

        StoreSetting::query()->firstOrFail()->update($data);

        return back()->with('status', 'Tampilan app kasir berhasil diperbarui.');
    }
}
