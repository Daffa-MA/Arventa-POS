@php
    $previewProducts = $products->map(fn ($product) => [
        'name' => $product->name,
        'sku' => $product->sku,
        'type' => $product->type,
        'unit' => $product->unit,
        'price' => 'Rp '.number_format((float) $product->price, 0, ',', '.'),
        'stock' => $product->stock !== null ? rtrim(rtrim(number_format((float) $product->stock, 3, ',', '.'), '0'), ',').' '.$product->unit : null,
        'imageUrl' => $product->image_path ? Storage::disk('public')->url($product->image_path) : null,
    ])->values();
@endphp

<x-admin.shell
    :setting="$setting"
    active="app-preview"
    title="Tampilan App"
    subtitle="Preview UI aplikasi kasir Android berdasarkan setting App Kasir dan katalog aktif."
>
    <section
        x-data="{
            preview: {
                storeName: @js($setting->store_name),
                businessType: @js($setting->business_type),
                themeColor: @js($setting->theme_color),
                appLayout: @js($setting->app_layout),
                productCardStyle: @js($setting->product_card_style),
                imageSize: 'medium',
                imageRatio: 'wide',
                showSku: {{ $setting->show_sku_on_app ? 'true' : 'false' }},
                showStock: {{ $setting->show_stock_on_app ? 'true' : 'false' }},
            },
            previewProducts: @js($previewProducts),
            imageHeight() {
                return {
                    small: '52px',
                    medium: '78px',
                    large: '104px',
                }[this.preview.imageSize]
            },
            imageObjectClass() {
                return {
                    square: 'object-cover',
                    wide: 'object-cover',
                    portrait: 'object-cover object-top',
                }[this.preview.imageRatio]
            },
            gridClass() {
                return this.preview.productCardStyle === 'image' && this.preview.imageSize === 'large'
                    ? 'grid-cols-1'
                    : 'grid-cols-2'
            },
            listImageSizeClass() {
                return {
                    small: 'h-10 w-10',
                    medium: 'h-14 w-14',
                    large: 'h-20 w-20',
                }[this.preview.imageSize]
            }
        }"
        class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]"
    >
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Kontrol Preview</h2>
            <p class="mt-1 text-sm text-slate-500">Kontrol ini hanya untuk simulasi di halaman ini. Simpan perubahan permanen dari halaman Setting.</p>
            <div class="mt-6 grid gap-4">
                <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Layout
                    <select x-model="preview.appLayout" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium normal-case tracking-normal">
                        <option value="grid">Grid kartu</option>
                        <option value="list">List detail</option>
                        <option value="compact">Compact cepat</option>
                    </select>
                </label>
                <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Gaya kartu
                    <select x-model="preview.productCardStyle" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium normal-case tracking-normal">
                        <option value="minimal">Minimal</option>
                        <option value="image">Dengan area gambar</option>
                    </select>
                </label>
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4" x-show="preview.productCardStyle === 'image'" x-transition>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ukuran gambar</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm font-semibold transition active:scale-[0.97]" :class="preview.imageSize === 'small' ? 'border-transparent text-white' : 'border-slate-200 bg-white text-slate-700'" :style="preview.imageSize === 'small' ? `background-color: ${preview.themeColor}` : ''" @click="preview.imageSize = 'small'">Kecil</button>
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm font-semibold transition active:scale-[0.97]" :class="preview.imageSize === 'medium' ? 'border-transparent text-white' : 'border-slate-200 bg-white text-slate-700'" :style="preview.imageSize === 'medium' ? `background-color: ${preview.themeColor}` : ''" @click="preview.imageSize = 'medium'">Sedang</button>
                        <button type="button" class="rounded-xl border px-3 py-2 text-sm font-semibold transition active:scale-[0.97]" :class="preview.imageSize === 'large' ? 'border-transparent text-white' : 'border-slate-200 bg-white text-slate-700'" :style="preview.imageSize === 'large' ? `background-color: ${preview.themeColor}` : ''" @click="preview.imageSize = 'large'">Besar</button>
                    </div>
                    <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Rasio gambar
                        <select x-model="preview.imageRatio" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium normal-case tracking-normal">
                            <option value="wide">Landscape 16:9</option>
                            <option value="square">Kotak 1:1</option>
                            <option value="portrait">Portrait 3:4</option>
                        </select>
                    </label>
                </div>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium"><input type="checkbox" x-model="preview.showSku"> SKU</label>
                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium"><input type="checkbox" x-model="preview.showStock"> Stok</label>
                </div>
            </div>
        </div>

        <div class="mx-auto w-full max-w-sm rounded-[2rem] border border-slate-300 bg-slate-950 p-3 shadow-2xl shadow-slate-950/20">
            <div class="overflow-hidden rounded-[1.5rem] bg-slate-50">
                <div class="px-4 py-4 text-white" :style="`background-color: ${preview.themeColor}`">
                    <p class="text-sm font-semibold" x-text="preview.storeName"></p>
                    <p class="text-[11px] text-white/80" x-text="preview.businessType"></p>
                </div>
                <div class="min-h-[420px] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-bold text-slate-950">Produk & Layanan</p>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold text-white" :style="`background-color: ${preview.themeColor}`" x-text="preview.appLayout"></span>
                    </div>
                    <template x-if="previewProducts.length === 0">
                        <div class="flex min-h-[300px] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 text-center">
                            <p class="text-sm font-semibold text-slate-950">Belum ada item</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Tambahkan produk aktif di halaman Katalog.</p>
                        </div>
                    </template>
                    <template x-if="previewProducts.length > 0">
                        <div>
                            <div x-show="preview.appLayout === 'grid'" class="grid gap-2 transition-all duration-200" :class="gridClass()">
                                <template x-for="product in previewProducts" :key="product.name + product.sku">
                                    <div class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                        <div x-show="preview.productCardStyle === 'image'" class="mb-2 flex w-full max-w-full items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-sm font-bold transition-all duration-200" :style="`color: ${preview.themeColor}; height: ${imageHeight()}`">
                                            <template x-if="product.imageUrl">
                                                <img :src="product.imageUrl" :alt="product.name" class="h-full w-full" :class="imageObjectClass()">
                                            </template>
                                            <template x-if="!product.imageUrl">
                                                <span x-text="product.name.charAt(0)"></span>
                                            </template>
                                        </div>
                                        <p class="line-clamp-2 text-xs font-bold text-slate-950" x-text="product.name"></p>
                                        <p class="mt-1 text-[10px] text-slate-500"><span x-text="product.type"></span><span x-show="preview.showSku && product.sku" x-text="' - ' + product.sku"></span></p>
                                        <p x-show="preview.showStock" class="mt-1 text-[10px] text-slate-500" x-text="product.stock ? 'Stok ' + product.stock : 'Tanpa stok'"></p>
                                        <p class="mt-2 text-xs font-bold" :style="`color: ${preview.themeColor}`"><span x-text="product.price"></span> / <span x-text="product.unit"></span></p>
                                    </div>
                                </template>
                            </div>
                            <div x-show="preview.appLayout !== 'grid'" class="space-y-2">
                                <template x-for="product in previewProducts" :key="product.name + product.sku">
                                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm" :class="preview.appLayout === 'compact' ? 'py-2' : ''">
                                        <div x-show="preview.productCardStyle === 'image'" class="shrink-0 overflow-hidden rounded-xl bg-slate-100 transition-all duration-200" :class="listImageSizeClass()">
                                            <template x-if="product.imageUrl">
                                                <img :src="product.imageUrl" :alt="product.name" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!product.imageUrl">
                                                <div class="flex h-full w-full items-center justify-center text-xs font-bold" :style="`color: ${preview.themeColor}`" x-text="product.name.charAt(0)"></div>
                                            </template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-bold text-slate-950" x-text="product.name"></p>
                                            <p class="mt-0.5 text-[10px] text-slate-500"><span x-text="product.type"></span><span x-show="preview.showStock" x-text="product.stock ? ' - Stok ' + product.stock : ' - Tanpa stok'"></span></p>
                                        </div>
                                        <p class="text-right text-[11px] font-bold" :style="`color: ${preview.themeColor}`" x-text="product.price"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="border-t border-slate-200 bg-white p-4">
                    <button class="w-full rounded-xl py-2.5 text-sm font-semibold text-white" :style="`background-color: ${preview.themeColor}`">Bayar</button>
                </div>
            </div>
        </div>
    </section>
</x-admin.shell>
