<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);

        Product::query()->create($data);

        return back()->with('status', 'Produk atau layanan berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);

        $product->update($data);

        return back()->with('status', 'Produk atau layanan berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('status', 'Produk atau layanan berhasil dihapus.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $id = $product?->id ?? 'NULL';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:80', "unique:products,sku,{$id}"],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'type' => ['required', 'in:product,service,discount,fee,custom'],
            'pricing_rule' => ['nullable', 'in:normal,free_threshold,discount,fee'],
            'unit' => ['required', 'in:pcs,ml,gram,kg,meter,trx'],
            'price' => ['required', 'numeric', 'between:-999999999.99,999999999.99'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'free_quantity' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];

        $pricingRule = $data['pricing_rule'] ?? match ($data['type']) {
            'discount', 'fee' => $data['type'],
            default => filled($data['free_quantity'] ?? null) ? 'free_threshold' : 'normal',
        };

        unset($data['pricing_rule']);

        if ($pricingRule === 'discount') {
            $data['type'] = 'discount';
            $data['unit'] = 'trx';
            $data['price'] = -abs((float) $data['price']);
            $data['stock'] = null;
            $data['free_quantity'] = null;
        } elseif ($pricingRule === 'fee') {
            $data['type'] = 'fee';
            $data['unit'] = 'trx';
            $data['price'] = abs((float) $data['price']);
            $data['stock'] = null;
            $data['free_quantity'] = null;
        } elseif ($pricingRule !== 'free_threshold') {
            $data['free_quantity'] = null;
        }

        return $data;
    }
}
