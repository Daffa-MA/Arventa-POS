<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorefrontController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $posInstanceId = $request->user()?->pos_instance_id;
        abort_unless($posInstanceId, 403, 'Akun kasir belum terhubung ke POS.');

        $setting = StoreSetting::query()->where('pos_instance_id', $posInstanceId)->first();

        if ($setting?->logo_path) {
            $setting->setAttribute('logo_url', Storage::disk('public')->url($setting->logo_path));
        }

        if ($setting?->qris_image_path) {
            $setting->setAttribute('qris_image_url', Storage::disk('public')->url($setting->qris_image_path));
        }

        if ($setting?->receipt_qr_image_path) {
            $setting->setAttribute('receipt_qr_image_url', Storage::disk('public')->url($setting->receipt_qr_image_path));
        }

        return response()->json([
            'store' => $setting,
            'products' => Product::query()
                ->where('pos_instance_id', $posInstanceId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function (Product $product): array {
                    $data = $product->toArray();
                    $data['image_url'] = $product->image_path
                        ? Storage::disk('public')->url($product->image_path)
                        : null;

                    return $data;
                }),
        ]);
    }
}
