<x-admin.shell
    :setting="$setting"
    active="products"
    title="Katalog"
    subtitle="Kelola produk dan layanan yang akan tersedia di aplikasi kasir Android."
>
    <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Tambah Produk atau Layanan</h2>
            <form
                method="post"
                action="{{ route('admin.products.store') }}"
                enctype="multipart/form-data"
                x-data="{ imagePreview: null }"
                class="mt-6 grid gap-4"
            >
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Foto barang</label>
                        <label class="mt-1 flex cursor-pointer items-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 transition hover:border-slate-400 hover:bg-white">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white text-slate-400 ring-1 ring-slate-200">
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" alt="Preview foto barang" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!imagePreview">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                                </template>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-950">Upload foto produk</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Format JPG, PNG, atau WebP. Maksimal 2MB.</p>
                            </div>
                            <input
                                name="image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="sr-only"
                                @change="imagePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                            >
                        </label>
                        @error('image')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama item</label>
                        <input name="name" value="{{ old('name') }}" placeholder="Contoh: Gula Pasir" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                        @error('name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">SKU opsional</label>
                        <input name="sku" value="{{ old('sku') }}" placeholder="ARV-ITEM-001" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipe</label>
                        <select name="type" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                            <option value="product">Produk</option>
                            <option value="service">Layanan</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</label>
                        <select name="unit" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                            <option value="pcs">Pcs / layanan</option>
                            <option value="ml">Mililiter (ml)</option>
                            <option value="gram">Gram</option>
                            <option value="kg">Kilogram</option>
                            <option value="meter">Meter</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Harga</label>
                        <input name="price" type="number" step="0.01" value="{{ old('price') }}" placeholder="18000" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok</label>
                        <input name="stock" type="number" step="0.001" value="{{ old('stock') }}" placeholder="Kosongkan untuk layanan" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 sm:col-span-2">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-blue-600" checked>
                        Aktif dan tampil di aplikasi kasir
                    </label>
                </div>
                <button class="rounded-xl px-4 py-3 text-sm font-semibold text-white transition hover:brightness-95 active:scale-[0.97]" style="background-color: var(--accent)">Tambah Item</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Daftar Katalog</h2>
                    <p class="text-sm text-slate-500">{{ $products->count() }} item</p>
                </div>
            </div>
            @if ($products->isEmpty())
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-slate-950">Katalog masih kosong</h3>
                    <p class="mt-1 max-w-sm text-sm text-slate-500">Tambahkan produk atau layanan pertama dari form di samping.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Item</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Satuan</th>
                                <th class="px-4 py-3">Harga</th>
                                <th class="px-4 py-3">Stok</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($products as $product)
                                <tr class="transition hover:bg-blue-50/60">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                                                @if ($product->image_path)
                                                    <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                                @else
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-950">{{ $product->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $product->sku ?: 'Tanpa SKU' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">{{ $product->type }}</td>
                                    <td class="px-4 py-4">{{ $product->unit }}</td>
                                    <td class="px-4 py-4 font-semibold">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4">{{ $product->stock !== null ? rtrim(rtrim(number_format((float) $product->stock, 3, ',', '.'), '0'), ',').' '.$product->unit : '-' }}</td>
                                    <td class="px-4 py-4"><span class="rounded-full {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-semibold">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="post" action="{{ route('admin.products.destroy', $product) }}">
                                            @csrf
                                            @method('delete')
                                            <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 active:scale-[0.97]">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</x-admin.shell>
