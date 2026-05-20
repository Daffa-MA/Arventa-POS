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
        return response()->json([
            'store' => StoreSetting::query()->first(),
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
