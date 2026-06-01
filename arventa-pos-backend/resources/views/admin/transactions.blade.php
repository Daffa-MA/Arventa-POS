<x-admin.shell
    :setting="$setting"
    active="transactions"
    title="Transaksi"
    subtitle="Pantau checkout terbaru dari aplikasi kasir Android."
>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Riwayat Transaksi</h2>
                <p class="mt-1 text-sm text-slate-500">Pisahkan transaksi berdasarkan perangkat kasir yang sudah pairing.</p>
            </div>

            <form method="GET" action="{{ route('admin.transactions') }}" class="grid w-full gap-3 sm:w-auto sm:min-w-[36rem] sm:grid-cols-[1.2fr_1fr_1fr_auto_auto] sm:items-end">
                <div class="flex flex-col gap-2">
                    <label for="device-filter" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perangkat Kasir</label>
                    <select
                        id="device-filter"
                        name="device"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        onchange="this.form.submit()"
                    >
                        <option value="all" @selected($selectedDevice === 'all')>Semua perangkat</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}" @selected((string) $selectedDevice === (string) $device->id)>
                                {{ $device->device_name }}{{ $device->user?->name ? ' - '.$device->user->name : '' }}
                            </option>
                        @endforeach
                        <option value="unknown" @selected($selectedDevice === 'unknown')>Tanpa data perangkat</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label for="date-from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dari</label>
                    <input
                        id="date-from"
                        type="date"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>
                <div class="flex flex-col gap-2">
                    <label for="date-to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai</label>
                    <input
                        id="date-to"
                        type="date"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>
                <button type="submit" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Filter
                </button>
                <button
                    type="submit"
                    formaction="{{ route('admin.transactions.export') }}"
                    class="h-11 rounded-xl bg-slate-950 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                >
                    Export Excel
                </button>
            </form>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($sales as $sale)
                <div class="px-6 py-4 transition hover:bg-slate-50">
                    <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-start">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-950">{{ $sale->invoice_number }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                <span>{{ $sale->created_at->format('d M Y H:i') }}</span>
                                <span class="text-slate-300">/</span>
                                <span>{{ $sale->items->count() }} item</span>
                                <span class="text-slate-300">/</span>
                                <span>{{ strtoupper($sale->payment_method ?? 'cash') }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $sale->cashier_device_name ?? $sale->cashierDevice?->device_name ?? 'Tanpa perangkat' }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $sale->cashier?->name ?? 'Kasir' }}
                                </span>
                            </div>
                        </div>
                        <div class="text-left lg:text-right">
                            <p class="text-sm font-bold" style="color: var(--accent)">Rp {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs text-slate-500">Subtotal Rp {{ number_format((float) $sale->subtotal, 0, ',', '.') }}</p>
                        </div>
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
