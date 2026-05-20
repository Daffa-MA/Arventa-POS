<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:40'],
        ]);

        $setting = StoreSetting::query()->firstOrFail();
        $products = Product::query()
            ->whereIn('id', collect($payload['items'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $lines = collect($payload['items'])->map(function (array $item) use ($products): array {
            $product = $products[$item['product_id']];
            $lineTotal = (float) $product->price * $item['quantity'];

            if ($product->stock !== null && (float) $product->stock < (float) $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Stok {$product->name} tidak cukup."],
                ]);
            }

            return [
                'product' => $product,
                'quantity' => $item['quantity'],
                'line_total' => $lineTotal,
            ];
        });

        $subtotal = $lines->sum('line_total');
        $taxTotal = round($subtotal * ((float) $setting->tax_rate / 100), 2);
        $serviceTotal = round($subtotal * ((float) $setting->service_charge_rate / 100), 2);
        $grandTotal = $subtotal + $taxTotal + $serviceTotal;

        if ((float) $payload['paid_amount'] < $grandTotal) {
            throw ValidationException::withMessages([
                'paid_amount' => ['Nominal bayar kurang dari total transaksi.'],
            ]);
        }

        $sale = DB::transaction(function () use ($request, $payload, $lines, $subtotal, $taxTotal, $serviceTotal, $grandTotal): Sale {
            $sale = Sale::query()->create([
                'invoice_number' => 'ARV-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'cashier_id' => $request->user()?->id,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'service_charge_total' => $serviceTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => $payload['paid_amount'],
                'change_amount' => (float) $payload['paid_amount'] - $grandTotal,
                'payment_method' => $payload['payment_method'] ?? 'cash',
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $sale->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $line['quantity'],
                    'unit' => $product->unit,
                    'line_total' => $line['line_total'],
                ]);

                if ($product->stock !== null) {
                    $product->decrement('stock', $line['quantity']);
                }
            }

            return $sale->load('items');
        });

        return response()->json(['sale' => $sale], 201);
    }
}
