<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $posInstanceId = $this->posInstanceId($request);

        return view('admin.dashboard', [
            'setting' => $this->setting($request),
            'products' => Product::query()->where('pos_instance_id', $posInstanceId)->latest()->get(),
            'sales' => Sale::query()->with('items')->where('pos_instance_id', $posInstanceId)->latest()->limit(8)->get(),
        ]);
    }

    public function settings(Request $request): View
    {
        return view('admin.settings', [
            'setting' => $this->setting($request),
        ]);
    }

    public function products(Request $request): View
    {
        return view('admin.products', [
            'setting' => $this->setting($request),
            'products' => Product::query()->where('pos_instance_id', $this->posInstanceId($request))->latest()->get(),
        ]);
    }

    public function appPreview(Request $request): View
    {
        return view('admin.app-preview', [
            'setting' => $this->setting($request),
            'products' => Product::query()
                ->where('pos_instance_id', $this->posInstanceId($request))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function transactions(Request $request): View
    {
        return view('admin.transactions', [
            'setting' => $this->setting($request),
            'sales' => Sale::query()
                ->with('items')
                ->where('pos_instance_id', $this->posInstanceId($request))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function updateSetting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'business_type' => ['required', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'receipt_qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'admin_brand_name' => ['required', 'string', 'max:80'],
            'admin_console_label' => ['required', 'string', 'max:80'],
            'admin_theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_sidebar_style' => ['required', 'in:light,dark,accent'],
            'admin_density' => ['required', 'in:comfortable,compact'],
            'address' => ['nullable', 'string', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:140'],
            'receipt_header_title' => ['nullable', 'string', 'max:120'],
            'receipt_header_subtitle' => ['nullable', 'string', 'max:140'],
            'receipt_header_notes' => ['nullable', 'string', 'max:500'],
            'receipt_header_alignment' => ['required', 'in:left,center,right'],
            'receipt_show_store_name' => ['sometimes', 'boolean'],
            'receipt_template' => ['required', 'in:classic,compact,detailed'],
            'receipt_paper_size' => ['required', 'in:58,80'],
            'receipt_show_logo' => ['sometimes', 'boolean'],
            'receipt_show_address' => ['sometimes', 'boolean'],
            'receipt_show_datetime' => ['sometimes', 'boolean'],
            'receipt_show_qris' => ['sometimes', 'boolean'],
            'receipt_show_business_type' => ['sometimes', 'boolean'],
            'receipt_show_payment_method' => ['sometimes', 'boolean'],
            'receipt_show_item_price' => ['sometimes', 'boolean'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'max:8'],
        ]) + [
            'receipt_show_logo' => $request->boolean('receipt_show_logo'),
            'receipt_show_store_name' => $request->boolean('receipt_show_store_name'),
            'receipt_show_address' => $request->boolean('receipt_show_address'),
            'receipt_show_datetime' => $request->boolean('receipt_show_datetime'),
            'receipt_show_qris' => $request->boolean('receipt_show_qris'),
            'receipt_show_business_type' => $request->boolean('receipt_show_business_type'),
            'receipt_show_payment_method' => $request->boolean('receipt_show_payment_method'),
            'receipt_show_item_price' => $request->boolean('receipt_show_item_price'),
        ];

        unset($data['logo']);
        unset($data['qris_image']);
        unset($data['receipt_qr_image']);

        $setting = $this->setting($request);

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

        if ($request->hasFile('receipt_qr_image')) {
            if ($setting->receipt_qr_image_path) {
                Storage::disk('public')->delete($setting->receipt_qr_image_path);
            }

            $data['receipt_qr_image_path'] = $request->file('receipt_qr_image')->store('receipt-qr', 'public');
        }

        $setting->update($data);

        return back()->with('status', 'Setting toko berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:120', 'confirmed'],
        ]);

        $user = $request->attributes->get('arventa_admin');

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Password lama tidak sesuai.'])
                ->withInput();
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        $posInstance = $request->attributes->get('arventa_pos_instance');
        if ($posInstance && $posInstance->admin_username === $user->username) {
            $posInstance->forceFill([
                'admin_password' => $data['password'],
                'admin_password_hash' => Hash::make($data['password']),
            ])->save();
        }

        return back()->with('status', 'Password admin berhasil diperbarui.');
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

        $this->setting($request)->update($data);

        return back()->with('status', 'Tampilan app kasir berhasil diperbarui.');
    }

    private function setting(Request $request): StoreSetting
    {
        return StoreSetting::query()
            ->where('pos_instance_id', $this->posInstanceId($request))
            ->firstOrFail();
    }

    private function posInstanceId(Request $request): int
    {
        return (int) $request->attributes->get('arventa_pos_instance')->id;
    }
}
