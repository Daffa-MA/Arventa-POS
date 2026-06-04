@php
    $totalProducts = $products->count();
    $totalSku = $products->filter(fn ($product) => filled($product->sku))->count();
    $recentSales = $sales ?? collect();
    $recentRevenue = $recentSales->sum(fn ($sale) => (float) $sale->grand_total);
@endphp

<x-admin.shell
    :setting="$setting"
    active="dashboard"
    title="Dashboard"
    subtitle="Ringkasan Arventa POS. Gunakan sidebar untuk membuka halaman khusus Setting, Katalog, Tampilan App, dan Transaksi."
>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="animate-[fade-up_300ms_ease-out_60ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-slate-500">Total Produk</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $totalProducts }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_120ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-slate-500">Total SKU</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12l4 6-10 12L2 9Z"/><path d="M11 3 8 9l4 12 4-12-3-6"/><path d="M2 9h20"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $totalSku }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_180ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-slate-500">Transaksi Terbaru</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $recentSales->count() }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_240ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-slate-500">Omzet Terbaru</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 6H3"/><path d="M21 12H3"/><path d="M16 18H3"/></svg>
                </span>
            </div>
            <p class="mt-4 text-2xl font-semibold text-slate-950">{{ $setting->currency }} {{ number_format($recentRevenue, 0, ',', '.') }}</p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1fr_0.85fr]">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-950/[0.03]">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <p class="font-semibold text-slate-950">Transaksi Terakhir</p>
                    <p class="mt-1 text-sm text-slate-500">Aktivitas checkout terbaru dari perangkat kasir.</p>
                </div>
                <a href="{{ route('admin.transactions') }}" class="rounded-full border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Lihat semua</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentSales->take(5) as $sale)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950">{{ $sale->invoice_number }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $sale->created_at?->format('d M Y H:i') }} / {{ strtoupper($sale->payment_method) }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-slate-950">{{ $setting->currency }} {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="font-semibold text-slate-950">Belum ada transaksi</p>
                        <p class="mt-1 text-sm text-slate-500">Transaksi dari aplikasi kasir akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
            <a href="{{ route('admin.settings') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="font-semibold text-slate-950">Setting Toko</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Atur identitas, struk, QRIS, pajak, dan tema Web Admin.</p>
            </a>
            <a href="{{ route('admin.products') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="font-semibold text-slate-950">Katalog</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Kelola produk, layanan, diskon, biaya, dan aturan harga khusus.</p>
            </a>
            <a href="{{ route('admin.devices') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="font-semibold text-slate-950">Perangkat Kasir</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Generate QR pairing dan pantau perangkat Android yang aktif.</p>
            </a>
        </div>
    </section>
</x-admin.shell>
