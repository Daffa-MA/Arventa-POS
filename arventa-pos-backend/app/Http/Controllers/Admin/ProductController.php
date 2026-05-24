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

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:80', "unique:products,sku,{$id}"],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'type' => ['required', 'in:product,service,discount,fee,custom'],
            'unit' => ['required', 'in:pcs,ml,gram,kg,meter,trx'],
            'price' => ['required', 'numeric', 'between:-999999999.99,999999999.99'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
