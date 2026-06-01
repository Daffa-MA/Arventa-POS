<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; }
        th { background: #e2e8f0; font-weight: 700; }
        .number { mso-number-format: "0"; text-align: right; }
        .decimal { mso-number-format: "0.000"; text-align: right; }
        .text { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <h2>Export Transaksi {{ $setting->store_name }}</h2>
    <p>Diexport: {{ $exportedAt->format('d/m/Y H:i') }}</p>
    <p>
        Filter:
        Perangkat = {{ $filters['device'] ?: 'all' }},
        Dari = {{ $filters['date_from'] ?: '-' }},
        Sampai = {{ $filters['date_to'] ?: '-' }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Perangkat Kasir</th>
                <th>Kasir</th>
                <th>Metode Bayar</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Total Item</th>
                <th>Subtotal</th>
                <th>Pajak</th>
                <th>Service</th>
                <th>Total</th>
                <th>Dibayar</th>
                <th>Kembalian</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                @forelse ($sale->items as $item)
                    <tr>
                        <td class="text">{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                        <td>{{ $sale->created_at->format('H:i:s') }}</td>
                        <td>{{ $sale->cashier_device_name ?? $sale->cashierDevice?->device_name ?? 'Tanpa perangkat' }}</td>
                        <td>{{ $sale->cashier?->name ?? 'Kasir' }}</td>
                        <td>{{ strtoupper($sale->payment_method ?? 'cash') }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="decimal">{{ (float) $item->quantity }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="number">{{ (float) $item->unit_price }}</td>
                        <td class="number">{{ (float) $item->line_total }}</td>
                        <td class="number">{{ (float) $sale->subtotal }}</td>
                        <td class="number">{{ (float) $sale->tax_total }}</td>
                        <td class="number">{{ (float) $sale->service_charge_total }}</td>
                        <td class="number">{{ (float) $sale->grand_total }}</td>
                        <td class="number">{{ (float) $sale->paid_amount }}</td>
                        <td class="number">{{ (float) $sale->change_amount }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text">{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                        <td>{{ $sale->created_at->format('H:i:s') }}</td>
                        <td>{{ $sale->cashier_device_name ?? $sale->cashierDevice?->device_name ?? 'Tanpa perangkat' }}</td>
                        <td>{{ $sale->cashier?->name ?? 'Kasir' }}</td>
                        <td>{{ strtoupper($sale->payment_method ?? 'cash') }}</td>
                        <td>-</td>
                        <td class="decimal">0</td>
                        <td>-</td>
                        <td class="number">0</td>
                        <td class="number">0</td>
                        <td class="number">{{ (float) $sale->subtotal }}</td>
                        <td class="number">{{ (float) $sale->tax_total }}</td>
                        <td class="number">{{ (float) $sale->service_charge_total }}</td>
                        <td class="number">{{ (float) $sale->grand_total }}</td>
                        <td class="number">{{ (float) $sale->paid_amount }}</td>
                        <td class="number">{{ (float) $sale->change_amount }}</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="17">Belum ada transaksi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
