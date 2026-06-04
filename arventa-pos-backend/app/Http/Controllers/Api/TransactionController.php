<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierDevice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.name' => ['nullable', 'string', 'max:120'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['nullable', 'numeric'],
            'items.*.line_total' => ['nullable', 'numeric'],
            'items.*.charged_quantity' => ['nullable', 'numeric', 'min:0'],
            'client_sale_id' => ['nullable', 'string', 'max:80'],
            'client_created_at' => ['nullable', 'date'],
            'catalog_synced_at' => ['nullable', 'date'],
            'subtotal' => ['nullable', 'numeric'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'service_charge_total' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'sync_source' => ['nullable', 'in:online,offline'],
        ]);

        $posInstanceId = $request->user()?->pos_instance_id;
        abort_unless($posInstanceId, 403, 'Akun kasir belum terhubung ke POS.');
        $cashierDevice = $this->cashierDevice($request, (int) $posInstanceId);
        $syncSource = $payload['sync_source'] ?? 'online';

        if (! empty($payload['client_sale_id'])) {
            $existingSale = Sale::query()
                ->with('items')
                ->where('pos_instance_id', $posInstanceId)
                ->where('client_sale_id', $payload['client_sale_id'])
                ->first();

            if ($existingSale) {
                return response()->json(['sale' => $existingSale]);
            }
        }

        $setting = StoreSetting::query()->where('pos_instance_id', $posInstanceId)->firstOrFail();
        $productIds = collect($payload['items'])->pluck('product_id')->filter()->values();
        $products = Product::query()
            ->where('pos_instance_id', $posInstanceId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $lines = collect($payload['items'])->map(function (array $item) use ($products, $syncSource): array {
            $productId = $item['product_id'] ?? null;

            if ($productId) {
                $product = $products->get($productId);

                if (! $product && ! $this->hasSnapshot($item)) {
                    throw ValidationException::withMessages([
                        'items' => ['Produk tidak ditemukan di POS ini.'],
                    ]);
                }

                $quantity = (float) $item['quantity'];
                $freeQuantity = (float) ($product?->free_quantity ?? 0);
                $paidQuantity = isset($item['charged_quantity'])
                    ? (float) $item['charged_quantity']
                    : ($freeQuantity > 0 && $quantity <= $freeQuantity ? 0 : $quantity);
                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product?->price;
                $lineTotal = isset($item['line_total'])
                    ? round((float) $item['line_total'], 2)
                    : round($unitPrice * $paidQuantity, 2);

                if ($syncSource === 'online' && $product?->stock !== null && (float) $product->stock < (float) $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$product->name} tidak cukup."],
                    ]);
                }

                return [
                    'product' => $product,
                    'name' => $item['name'] ?? $product?->name,
                    'unit' => $item['unit'] ?? $product?->unit,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            if (empty($item['name']) || empty($item['unit']) || ! isset($item['unit_price'])) {
                throw ValidationException::withMessages([
                    'items' => ['Item custom wajib memiliki nama, satuan, dan harga.'],
                ]);
            }

            $lineTotal = isset($item['line_total'])
                ? round((float) $item['line_total'], 2)
                : round((float) $item['unit_price'] * (float) $item['quantity'], 2);

            return [
                'product' => null,
                'name' => $item['name'],
                'unit' => $item['unit'],
                'unit_price' => (float) $item['unit_price'],
                'quantity' => $item['quantity'],
                'line_total' => $lineTotal,
            ];
        });

        $subtotal = round($lines->sum('line_total'), 2);
        $chargeableSubtotal = max(0, $subtotal);
        $taxTotal = isset($payload['tax_total'])
            ? round((float) $payload['tax_total'], 2)
            : round($chargeableSubtotal * ((float) $setting->tax_rate / 100), 2);
        $serviceTotal = isset($payload['service_charge_total'])
            ? round((float) $payload['service_charge_total'], 2)
            : round($chargeableSubtotal * ((float) $setting->service_charge_rate / 100), 2);
        $grandTotal = isset($payload['grand_total'])
            ? round((float) $payload['grand_total'], 2)
            : max(0, $subtotal + $taxTotal + $serviceTotal);
        $expectedGrandTotal = round(max(0, $subtotal + $taxTotal + $serviceTotal), 2);

        if (abs($grandTotal - $expectedGrandTotal) > 0.01) {
            throw ValidationException::withMessages([
                'grand_total' => ['Total transaksi tidak cocok dengan snapshot item.'],
            ]);
        }

        if ((float) $payload['paid_amount'] < $grandTotal) {
            throw ValidationException::withMessages([
                'paid_amount' => ['Nominal bayar kurang dari total transaksi.'],
            ]);
        }

        $sale = DB::transaction(function () use ($request, $payload, $lines, $subtotal, $taxTotal, $serviceTotal, $grandTotal, $posInstanceId, $cashierDevice, $syncSource): Sale {
            if ($cashierDevice) {
                $cashierDevice->forceFill(['last_seen_at' => now()])->save();
            }

            $sale = Sale::query()->create([
                'pos_instance_id' => $posInstanceId,
                'invoice_number' => 'ARV-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'client_sale_id' => $payload['client_sale_id'] ?? null,
                'cashier_id' => $request->user()?->id,
                'cashier_device_id' => $cashierDevice?->id,
                'cashier_device_name' => $cashierDevice?->device_name,
                'client_created_at' => $this->optionalDate($payload['client_created_at'] ?? null),
                'catalog_synced_at' => $this->optionalDate($payload['catalog_synced_at'] ?? null),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'service_charge_total' => $serviceTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => $payload['paid_amount'],
                'change_amount' => (float) $payload['paid_amount'] - $grandTotal,
                'payment_method' => $payload['payment_method'] ?? 'cash',
                'sync_source' => $syncSource,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $sale->items()->create([
                    'product_id' => $product?->id,
                    'name' => $line['name'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'unit' => $line['unit'],
                    'line_total' => $line['line_total'],
                ]);

                if ($product?->stock !== null) {
                    $product->decrement('stock', $line['quantity']);
                }
            }

            return $sale->load('items');
        });

        return response()->json(['sale' => $sale], 201);
    }

    private function hasSnapshot(array $item): bool
    {
        return ! empty($item['name']) && ! empty($item['unit']) && isset($item['unit_price']);
    }

    private function optionalDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value);
    }

    private function cashierDevice(Request $request, int $posInstanceId): ?CashierDevice
    {
        $token = $request->user()?->currentAccessToken();
        $tokenName = '';

        if (is_object($token)) {
            $tokenName = method_exists($token, 'getAttribute')
                ? (string) $token->getAttribute('name')
                : (string) ($token->name ?? '');
        }

        if (! preg_match('/^cashier-device-(\d+)$/', $tokenName, $matches)) {
            return null;
        }

        return CashierDevice::query()
            ->whereKey((int) $matches[1])
            ->where('pos_instance_id', $posInstanceId)
            ->where('user_id', $request->user()?->id)
            ->whereNull('revoked_at')
            ->first();
    }
}
