<x-admin.shell
    :setting="$setting"
    active="products"
    title="Katalog"
    subtitle="Kelola produk dan layanan yang akan tersedia di aplikasi kasir Android."
>
    @php
        $unitOptions = [
            'pcs' => 'Pcs / layanan',
            'ml' => 'Mililiter (ml)',
            'gram' => 'Gram',
            'kg' => 'Kilogram',
            'meter' => 'Meter',
            'trx' => 'Transaksi',
        ];
        $typeOptions = [
            'product' => 'Produk',
            'service' => 'Layanan',
            'custom' => 'Item Fleksibel',
        ];
        $typeLabels = [
            'product' => 'Produk',
            'service' => 'Layanan',
            'discount' => 'Diskon',
            'fee' => 'Biaya Tambahan',
            'custom' => 'Item Fleksibel',
        ];
        $pricingRuleOptions = [
            'normal' => [
                'label' => 'Harga normal',
                'description' => 'Produk atau layanan dihitung sesuai harga dan jumlah.',
            ],
            'free_threshold' => [
                'label' => 'Batas gratis',
                'description' => 'Gratis sampai batas tertentu. Jika lewat batas, seluruh jumlah ditagih.',
            ],
            'discount' => [
                'label' => 'Diskon nominal',
                'description' => 'Muncul di menu Diskon pada Cart Android, bukan di grid produk.',
            ],
            'fee' => [
                'label' => 'Biaya tambahan',
                'description' => 'Tambahan nominal transaksi dari katalog.',
            ],
        ];
        $createOldType = old('type', 'product');
        $createItemType = in_array($createOldType, array_keys($typeOptions), true) ? $createOldType : 'product';
        $createPricingRule = old('pricing_rule', in_array($createOldType, ['discount', 'fee'], true) ? $createOldType : ((old('free_quantity') !== null && old('free_quantity') !== '') ? 'free_threshold' : 'normal'));
    @endphp

    <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Tambah Item Katalog</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Produk tetap dibuat dari katalog. Diskon dan batas gratis diatur sebagai aturan harga agar pemakaiannya lebih rapi di aplikasi kasir.</p>
            <form
                method="post"
                action="{{ route('admin.products.store') }}"
                enctype="multipart/form-data"
                x-data="{ imagePreview: null, itemType: @js($createItemType), pricingRule: @js($createPricingRule) }"
                class="mt-6 grid gap-4"
            >
                @csrf
                <input type="hidden" name="type" :value="pricingRule === 'discount' ? 'discount' : (pricingRule === 'fee' ? 'fee' : itemType)">
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
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipe item</label>
                        <select x-model="itemType" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule === 'discount' || pricingRule === 'fee'">
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500" x-show="pricingRule === 'discount' || pricingRule === 'fee'">Tipe transaksi otomatis mengikuti aturan harga.</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</label>
                        <select name="unit" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule === 'discount' || pricingRule === 'fee'">
                            @foreach ($unitOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('unit', 'pcs') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="unit" value="trx" x-show="pricingRule === 'discount' || pricingRule === 'fee'" :disabled="pricingRule !== 'discount' && pricingRule !== 'fee'">
                        @error('unit')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Harga / nominal</label>
                        <input name="price" type="number" step="0.01" value="{{ old('price') }}" placeholder="18000" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                        <p class="mt-1 text-xs text-slate-500" x-text="pricingRule === 'discount' ? 'Isi nominal positif, sistem akan menyimpannya sebagai minus.' : 'Harga per satuan atau nominal transaksi.'"></p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok</label>
                        <input name="stock" type="number" step="0.001" value="{{ old('stock') }}" placeholder="Kosongkan untuk layanan" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule === 'discount' || pricingRule === 'fee'">
                    </div>
                    <div class="sm:col-span-2 rounded-2xl border p-4" style="border-color: color-mix(in srgb, var(--accent) 16%, #e2e8f0); background-color: color-mix(in srgb, var(--accent) 5%, #ffffff);">
                        <label class="text-xs font-semibold uppercase tracking-wide" style="color: var(--accent)">Aturan harga</label>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($pricingRuleOptions as $value => $option)
                                <label
                                    class="flex cursor-pointer gap-3 rounded-xl border bg-white p-3 text-sm transition"
                                    :class="pricingRule === '{{ $value }}' ? '' : 'border-slate-200 hover:border-slate-300'"
                                    :style="pricingRule === '{{ $value }}' ? 'border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 18%, transparent); background-color: color-mix(in srgb, var(--accent) 4%, #ffffff)' : ''"
                                >
                                    <input type="radio" name="pricing_rule" value="{{ $value }}" x-model="pricingRule" class="mt-1 border-slate-300" style="accent-color: var(--accent)">
                                    <span>
                                        <span class="block font-semibold text-slate-950">{{ $option['label'] }}</span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $option['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-4" x-show="pricingRule === 'free_threshold'">
                            <label class="text-xs font-semibold uppercase tracking-wide" style="color: var(--accent)">Batas gratis</label>
                            <input name="free_quantity" type="number" step="0.001" value="{{ old('free_quantity') }}" placeholder="Contoh: 100 untuk gratis sampai 100ml" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule !== 'free_threshold'">
                            <p class="mt-2 text-xs leading-5 text-slate-600">Jika jumlah transaksi masih di bawah atau sama dengan batas ini, item menjadi Rp0. Jika melewati batas, seluruh jumlah ditagih.</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Contoh: harga Rp1.000/ml, batas gratis 100ml. Input 100ml = Rp0, input 150ml = Rp150.000.</p>
                        </div>
                        @error('free_quantity')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 sm:col-span-2">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" style="accent-color: var(--accent)" checked>
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
                        <p class="mt-1 max-w-sm text-sm text-slate-500">Tambahkan produk, layanan, diskon, atau biaya tambahan pertama dari form di samping.</p>
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
                        <tbody class="divide-y divide-slate-100" x-data="{ editing: null }">
                            @foreach ($products as $product)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                                                @if ($product->image_path)
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                                @else
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-950">{{ $product->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $product->sku ?: 'Tanpa SKU' }}</p>
                                                @if ($product->free_quantity !== null && (float) $product->free_quantity > 0)
                                                    <p class="mt-0.5 text-xs font-medium" style="color: var(--accent)">Gratis sampai {{ rtrim(rtrim(number_format((float) $product->free_quantity, 3, ',', '.'), '0'), ',') }} {{ $unitOptions[$product->unit] ?? $product->unit }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">{{ $typeLabels[$product->type] ?? $product->type }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $unitOptions[$product->unit] ?? $product->unit }}</span>
                                    </td>
                                    <td class="px-4 py-4 font-semibold">{{ (float) $product->price < 0 ? '-Rp '.number_format(abs((float) $product->price), 0, ',', '.') : 'Rp '.number_format((float) $product->price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4">{{ $product->stock !== null ? rtrim(rtrim(number_format((float) $product->stock, 3, ',', '.'), '0'), ',').' '.$product->unit : '-' }}</td>
                                    <td class="px-4 py-4"><span class="rounded-full {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-semibold">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 active:scale-[0.97]" @click="editing = editing === {{ $product->id }} ? null : {{ $product->id }}">
                                                Edit
                                            </button>
                                            <form method="post" action="{{ route('admin.products.destroy', $product) }}">
                                                @csrf
                                                @method('delete')
                                                <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 active:scale-[0.97]">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr x-cloak x-show="editing === {{ $product->id }}" x-transition>
                                    <td colspan="7" class="bg-slate-50 px-6 py-5">
                                        @php
                                            $editOldType = old('type', $product->type);
                                            $editItemType = in_array($editOldType, array_keys($typeOptions), true) ? $editOldType : 'product';
                                            $editPricingRule = old('pricing_rule', in_array($editOldType, ['discount', 'fee'], true) ? $editOldType : (((float) old('free_quantity', $product->free_quantity) > 0) ? 'free_threshold' : 'normal'));
                                        @endphp
                                        <form
                                            method="post"
                                            action="{{ route('admin.products.update', $product) }}"
                                            enctype="multipart/form-data"
                                            x-data="{ itemType: @js($editItemType), pricingRule: @js($editPricingRule) }"
                                            class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2"
                                        >
                                            @csrf
                                            @method('put')
                                            <input type="hidden" name="type" :value="pricingRule === 'discount' ? 'discount' : (pricingRule === 'fee' ? 'fee' : itemType)">
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama item</label>
                                                <input name="name" value="{{ old('name', $product->name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</label>
                                                <input name="sku" value="{{ old('sku', $product->sku) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipe item</label>
                                                <select x-model="itemType" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule === 'discount' || pricingRule === 'fee'">
                                                    @foreach ($typeOptions as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</label>
                                                <select name="unit" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule === 'discount' || pricingRule === 'fee'">
                                                    @foreach ($unitOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('unit', $product->unit) === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="unit" value="trx" x-show="pricingRule === 'discount' || pricingRule === 'fee'" :disabled="pricingRule !== 'discount' && pricingRule !== 'fee'">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Harga / nominal</label>
                                                <input name="price" type="number" step="0.01" value="{{ old('price', $product->price) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                                                <p class="mt-1 text-xs text-slate-500" x-text="pricingRule === 'discount' ? 'Isi nominal positif, sistem akan menyimpannya sebagai minus.' : 'Harga per satuan atau nominal transaksi.'"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok sesuai satuan</label>
                                                <input name="stock" type="number" step="0.001" value="{{ old('stock', $product->stock) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule === 'discount' || pricingRule === 'fee'">
                                            </div>
                                            <div class="sm:col-span-2 rounded-2xl border p-4" style="border-color: color-mix(in srgb, var(--accent) 16%, #e2e8f0); background-color: color-mix(in srgb, var(--accent) 5%, #ffffff);">
                                                <label class="text-xs font-semibold uppercase tracking-wide" style="color: var(--accent)">Aturan harga</label>
                                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                                    @foreach ($pricingRuleOptions as $value => $option)
                                                        <label
                                                            class="flex cursor-pointer gap-3 rounded-xl border bg-white p-3 text-sm transition"
                                                            :class="pricingRule === '{{ $value }}' ? '' : 'border-slate-200 hover:border-slate-300'"
                                                            :style="pricingRule === '{{ $value }}' ? 'border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 18%, transparent); background-color: color-mix(in srgb, var(--accent) 4%, #ffffff)' : ''"
                                                        >
                                                            <input type="radio" name="pricing_rule" value="{{ $value }}" x-model="pricingRule" class="mt-1 border-slate-300" style="accent-color: var(--accent)">
                                                            <span>
                                                                <span class="block font-semibold text-slate-950">{{ $option['label'] }}</span>
                                                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $option['description'] }}</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <div class="mt-4" x-show="pricingRule === 'free_threshold'">
                                                    <label class="text-xs font-semibold uppercase tracking-wide" style="color: var(--accent)">Batas gratis</label>
                                                    <input name="free_quantity" type="number" step="0.001" value="{{ old('free_quantity', $product->free_quantity) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium" :disabled="pricingRule !== 'free_threshold'">
                                                    <p class="mt-1 text-xs text-slate-500">Rule tetap per transaksi. Jika jumlah melewati batas, seluruh jumlah ditagih. Contoh batas 100ml: 100ml = Rp0, 150ml = 150ml x harga/ml.</p>
                                                </div>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ganti foto opsional</label>
                                                <input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                                            </div>
                                            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700">
                                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" style="accent-color: var(--accent)" @checked(old('is_active', $product->is_active))>
                                                Aktif
                                            </label>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="editing = null">Batal</button>
                                                <button class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-95 active:scale-[0.97]" style="background-color: var(--accent)">Simpan Perubahan</button>
                                            </div>
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
