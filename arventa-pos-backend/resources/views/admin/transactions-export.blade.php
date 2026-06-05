<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; }
        h2 { margin: 0 0 6px; font-size: 18px; }
        p { margin: 0 0 6px; color: #475569; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 7px 9px;
            vertical-align: middle;
            white-space: nowrap;
        }
        th {
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            text-align: left;
        }
        .number { mso-number-format: "#,##0"; text-align: right; }
        .decimal { mso-number-format: "#,##0.###"; text-align: right; }
        .money { mso-number-format: "#,##0.00"; text-align: right; }
        .text, .date, .time { mso-number-format: "\@"; }
        .muted { color: #64748b; }
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
        <colgroup>
            <col style="width: 150px">
            <col style="width: 105px">
            <col style="width: 85px">
            <col style="width: 165px">
            <col style="width: 120px">
            <col style="width: 105px">
            <col style="width: 180px">
            <col style="width: 70px">
            <col style="width: 85px">
            <col style="width: 120px">
            <col style="width: 120px">
            <col style="width: 120px">
            <col style="width: 120px">
            <col style="width: 120px">
            <col style="width: 125px">
            <col style="width: 125px">
            <col style="width: 125px">
        </colgroup>
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
                        <td class="date">{{ $sale->created_at->format('d/m/Y') }}</td>
                        <td class="time">{{ $sale->created_at->format('H:i:s') }}</td>
                        <td>{{ $sale->cashier_device_name ?? $sale->cashierDevice?->device_name ?? 'Tanpa perangkat' }}</td>
                        <td>{{ $sale->cashier?->name ?? 'Kasir' }}</td>
                        <td>{{ strtoupper($sale->payment_method ?? 'cash') }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="decimal">{{ (float) $item->quantity }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="money">{{ (float) $item->unit_price }}</td>
                        <td class="money">{{ (float) $item->line_total }}</td>
                        <td class="money">{{ (float) $sale->subtotal }}</td>
                        <td class="money">{{ (float) $sale->tax_total }}</td>
                        <td class="money">{{ (float) $sale->service_charge_total }}</td>
                        <td class="money">{{ (float) $sale->grand_total }}</td>
                        <td class="money">{{ (float) $sale->paid_amount }}</td>
                        <td class="money">{{ (float) $sale->change_amount }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text">{{ $sale->invoice_number }}</td>
                        <td class="date">{{ $sale->created_at->format('d/m/Y') }}</td>
                        <td class="time">{{ $sale->created_at->format('H:i:s') }}</td>
                        <td>{{ $sale->cashier_device_name ?? $sale->cashierDevice?->device_name ?? 'Tanpa perangkat' }}</td>
                        <td>{{ $sale->cashier?->name ?? 'Kasir' }}</td>
                        <td>{{ strtoupper($sale->payment_method ?? 'cash') }}</td>
                        <td>-</td>
                        <td class="decimal">0</td>
                        <td>-</td>
                        <td class="money">0</td>
                        <td class="money">0</td>
                        <td class="money">{{ (float) $sale->subtotal }}</td>
                        <td class="money">{{ (float) $sale->tax_total }}</td>
                        <td class="money">{{ (float) $sale->service_charge_total }}</td>
                        <td class="money">{{ (float) $sale->grand_total }}</td>
                        <td class="money">{{ (float) $sale->paid_amount }}</td>
                        <td class="money">{{ (float) $sale->change_amount }}</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="17" class="muted">Belum ada transaksi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
