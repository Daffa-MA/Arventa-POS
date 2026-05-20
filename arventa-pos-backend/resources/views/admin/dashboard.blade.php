@php
    $totalProducts = $products->count();
    $totalSku = $products->filter(fn ($product) => filled($product->sku))->count();
@endphp

<x-admin.shell
    :setting="$setting"
    active="dashboard"
    title="Dashboard"
    subtitle="Ringkasan Arventa POS. Gunakan sidebar untuk membuka halaman khusus Setting, Katalog, Tampilan App, dan Transaksi."
>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="animate-[fade-up_300ms_ease-out_60ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <p class="text-sm font-medium text-slate-500">Total Produk</p>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $totalProducts }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_120ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <p class="text-sm font-medium text-slate-500">Total SKU</p>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $totalSku }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_180ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <p class="text-sm font-medium text-slate-500">Pajak</p>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ number_format((float) $setting->tax_rate, 2, ',', '.') }}%</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_240ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <p class="text-sm font-medium text-slate-500">Mata Uang</p>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $setting->currency }}</p>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <a href="{{ route('admin.settings') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="font-semibold text-slate-950">Setting</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Atur identitas toko, tampilan Web Admin, pajak, service charge, dan konfigurasi app kasir.</p>
        </a>
        <a href="{{ route('admin.products') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="font-semibold text-slate-950">Katalog</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Kelola produk dan layanan dengan satuan pcs, ml, gram, kg, dan meter.</p>
        </a>
        <a href="{{ route('admin.app-preview') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="font-semibold text-slate-950">Tampilan App</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Lihat preview tampilan kasir Android berdasarkan setting app dan katalog aktif.</p>
        </a>
        <a href="{{ route('admin.transactions') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="font-semibold text-slate-950">Transaksi</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Pantau checkout terbaru dari aplikasi kasir Android.</p>
        </a>
    </section>
</x-admin.shell>
