<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class StorefrontController extends Controller
{
    public function sync(): JsonResponse
    {
        $setting = StoreSetting::query()->first();

        if ($setting?->logo_path) {
            $setting->setAttribute('logo_url', Storage::disk('public')->url($setting->logo_path));
        }

        if ($setting?->qris_image_path) {
            $setting->setAttribute('qris_image_url', Storage::disk('public')->url($setting->qris_image_path));
        }

        return response()->json([
            'store' => $setting,
            'products' => Product::query()
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
