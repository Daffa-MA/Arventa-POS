<?php

namespace App\Http\Middleware;

use App\Models\PosInstance;
use App\Models\StoreSetting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::query()
            ->with('posInstance')
            ->whereKey($request->session()->get('arventa_admin_id'))
            ->where('role', 'admin')
            ->where('is_active', true)
            ->first();

        if (! $user) {
            $request->session()->forget('arventa_admin_id');

            return redirect()->route('admin.login');
        }

        $posInstance = $user->posInstance ?: PosInstance::query()->orderBy('id')->first();

        if (! $posInstance) {
            $request->session()->forget('arventa_admin_id');

            return redirect()->route('admin.login')->withErrors('POS belum tersedia untuk akun admin ini.');
        }

        if (! $user->pos_instance_id) {
            $user->forceFill(['pos_instance_id' => $posInstance->id])->save();
            $user->setRelation('posInstance', $posInstance);
        }

        StoreSetting::query()->firstOrCreate([
            'pos_instance_id' => $posInstance->id,
        ], [
            'store_name' => $posInstance->store_name,
            'business_type' => 'retail',
            'admin_brand_name' => $posInstance->store_name,
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

        $request->attributes->set('arventa_admin', $user);
        $request->attributes->set('arventa_pos_instance', $posInstance);
        view()->share('currentAdmin', $user);
        view()->share('currentPosInstance', $posInstance);

        return $next($request);
    }
}
