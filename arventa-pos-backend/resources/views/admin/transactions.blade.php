<x-admin.shell
    :setting="$setting"
    active="transactions"
    title="Transaksi"
    subtitle="Pantau checkout terbaru dari aplikasi kasir Android."
>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-950">Riwayat Transaksi</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($sales as $sale)
                <div class="px-6 py-4 transition hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $sale->invoice_number }}</p>
                            <p class="text-sm text-slate-500">{{ $sale->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <p class="text-sm font-bold" style="color: var(--accent)">Rp {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <h3 class="font-semibold text-slate-950">Belum ada transaksi</h3>
                    <p class="mt-1 max-w-xs text-sm text-slate-500">Transaksi dari aplikasi kasir Android akan tampil di sini.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($sales->hasPages())
        <div>{{ $sales->links() }}</div>
    @endif
</x-admin.shell>
