@php
    $money = fn ($value) => ($setting->currency === 'IDR' ? 'Rp' : $setting->currency.' ').number_format((float) $value, 0, ',', '.');
    $previewProducts = $products->map(fn ($product) => [
        'name' => $product->name,
        'sku' => $product->sku,
        'type' => $product->type,
        'unit' => $product->unit,
        'price' => $money($product->price),
        'priceValue' => (float) $product->price,
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
                themeColor: @js(old('theme_color', $setting->theme_color)),
                textColor: @js(old('app_text_color', $setting->app_text_color ?? '#0F172A')),
                secondaryTextColor: @js(old('app_secondary_text_color', $setting->app_secondary_text_color ?? '#64748B')),
                priceTextColor: @js(old('app_price_text_color', $setting->app_price_text_color ?? '#0F172A')),
                appLayout: @js(old('app_layout', $setting->app_layout)),
                productCardStyle: @js(old('product_card_style', $setting->product_card_style)),
                orientation: @js(old('pos_orientation', $setting->pos_orientation ?? 'portrait')),
                imageSize: 'medium',
                imageRatio: 'wide',
                showSearch: {{ old('show_search_on_app', $setting->show_search_on_app ?? true) ? 'true' : 'false' }},
                showCart: {{ old('show_cart_on_app', $setting->show_cart_on_app ?? true) ? 'true' : 'false' }},
                cartPosition: @js(old('cart_position', $setting->cart_position ?? 'bottom')),
                checkoutPosition: @js(old('checkout_position', $setting->checkout_position ?? 'bottom')),
                currency: @js($setting->currency ?? 'IDR'),
                showOrderSummary: {{ old('show_order_summary_on_app', $setting->show_order_summary_on_app ?? true) ? 'true' : 'false' }},
                showSku: {{ old('show_sku_on_app', $setting->show_sku_on_app) ? 'true' : 'false' }},
                showStock: {{ old('show_stock_on_app', $setting->show_stock_on_app) ? 'true' : 'false' }},
            },
            previewProducts: @js($previewProducts),
            cart: {},
            qty(product) {
                return this.cart[product.name] || 0
            },
            step(product) {
                return {
                    pcs: 1,
                    ml: 5,
                    gram: 100,
                    kg: 0.1,
                    meter: 0.1,
                }[product.unit] || 1
            },
            formatQty(value) {
                const numeric = Number(value || 0)
                return Number.isInteger(numeric) ? `${numeric}` : numeric.toFixed(3).replace(/\.?0+$/, '')
            },
            addQty(product) {
                this.cart[product.name] = Number((this.qty(product) + this.step(product)).toFixed(3))
            },
            removeQty(product) {
                const next = Number((this.qty(product) - this.step(product)).toFixed(3))
                if (next <= 0) {
                    delete this.cart[product.name]
                } else {
                    this.cart[product.name] = next
                }
            },
            selectedProduct() {
                return this.previewProducts.find((product) => this.qty(product) > 0) || null
            },
            selectedQuantity() {
                const product = this.selectedProduct()
                return product ? this.qty(product) : 0
            },
            formatCurrency(value) {
                const currency = this.preview.currency || 'IDR'
                const number = Number(value || 0).toLocaleString('id-ID')
                return currency === 'IDR' ? `Rp${number}` : `${currency} ${number}`
            },
            cartTotal() {
                return this.previewProducts.reduce((total, product) => total + (this.qty(product) * product.priceValue), 0)
            },
            cartLabel() {
                const product = this.selectedProduct()
                if (!product) return 'Belum ada item dipilih'
                const qty = this.selectedQuantity()
                return `${this.formatQty(qty)} ${product.unit} - ${product.name}`
            },
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
                if (this.preview.orientation === 'portrait') return 'grid-cols-1'

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
            },
            deviceFrameClass() {
                return this.preview.orientation === 'landscape'
                    ? 'max-w-3xl rounded-[2rem]'
                    : 'max-w-sm rounded-[2rem]'
            },
            deviceScreenClass() {
                return this.preview.orientation === 'landscape'
                    ? 'rounded-[1.45rem]'
                    : 'rounded-[1.5rem]'
            },
            contentHeightClass() {
                return this.preview.orientation === 'landscape'
                    ? 'min-h-[230px]'
                    : 'min-h-[420px]'
            },
            emptyStateHeightClass() {
                return this.preview.orientation === 'landscape'
                    ? 'min-h-[145px]'
                    : 'min-h-[300px]'
            },
            isSideCart() {
                return this.preview.showCart && this.preview.orientation === 'landscape' && this.preview.cartPosition === 'right'
            },
            isBottomCart() {
                return this.preview.showCart && !this.isSideCart()
            },
            cartCheckoutVisible() {
                return this.preview.checkoutPosition === 'cart' || this.preview.checkoutPosition === 'bottom'
            },
            bottomCheckoutBarVisible() {
                return this.preview.checkoutPosition === 'bottom' && !this.preview.showCart
            },
            productAreaClass() {
                return this.isSideCart()
                    ? 'grid gap-4 lg:grid-cols-[1fr_220px]'
                    : 'grid gap-3'
            },
            checkoutLabel() {
                return this.preview.showCart ? 'Checkout' : 'Bayar'
            }
        }"
        class="grid items-start gap-6 lg:grid-cols-[0.8fr_1.2fr]"
    >
        <form method="post" action="{{ route('admin.app-preview.update') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('put')

            <h2 class="text-lg font-semibold text-slate-950">Kontrol Preview</h2>
            <p class="mt-1 text-sm text-slate-500">Atur tampilan app kasir sambil melihat preview. Simpan untuk dikirim ke app Android lewat sync.</p>
            <div class="mt-6 grid gap-4">
                <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Warna app
                    <input name="theme_color" x-model="preview.themeColor" type="color" value="{{ old('theme_color', $setting->theme_color) }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-2 py-1">
                </label>
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Warna teks app</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Utama
                            <input name="app_text_color" x-model="preview.textColor" type="color" value="{{ old('app_text_color', $setting->app_text_color ?? '#0F172A') }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-2 py-1">
                        </label>
                        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Meta
                            <input name="app_secondary_text_color" x-model="preview.secondaryTextColor" type="color" value="{{ old('app_secondary_text_color', $setting->app_secondary_text_color ?? '#64748B') }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-2 py-1">
                        </label>
                        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Harga
                            <input name="app_price_text_color" x-model="preview.priceTextColor" type="color" value="{{ old('app_price_text_color', $setting->app_price_text_color ?? '#0F172A') }}" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-2 py-1">
                        </label>
                    </div>
                </div>
                <div class="grid gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mode layar</p>
                    <input type="hidden" name="pos_orientation" :value="preview.orientation">
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition active:scale-[0.97]" :class="preview.orientation === 'portrait' ? 'border-transparent text-white' : 'border-slate-200 bg-white text-slate-700'" :style="preview.orientation === 'portrait' ? `background-color: ${preview.themeColor}` : ''" @click="preview.orientation = 'portrait'">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="10" height="18" x="7" y="3" rx="2"/><path d="M12 17h.01"/></svg>
                            Portrait
                        </button>
                        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition active:scale-[0.97]" :class="preview.orientation === 'landscape' ? 'border-transparent text-white' : 'border-slate-200 bg-white text-slate-700'" :style="preview.orientation === 'landscape' ? `background-color: ${preview.themeColor}` : ''" @click="preview.orientation = 'landscape'">
                            <svg class="h-4 w-4 rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="10" height="18" x="7" y="3" rx="2"/><path d="M12 17h.01"/></svg>
                            Landscape
                        </button>
                    </div>
                </div>
                <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Layout
                    <select name="app_layout" x-model="preview.appLayout" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium normal-case tracking-normal">
                        <option value="grid">Grid kartu</option>
                        <option value="list">List detail</option>
                        <option value="compact">Compact cepat</option>
                    </select>
                </label>
                <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Gaya kartu
                    <select name="product_card_style" x-model="preview.productCardStyle" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium normal-case tracking-normal">
                        <option value="minimal">Minimal</option>
                        <option value="image">Dengan area gambar</option>
                    </select>
                </label>
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Halaman POS</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium">
                            <input type="checkbox" name="show_search_on_app" value="1" x-model="preview.showSearch">
                            Search bar
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium">
                            <input type="checkbox" name="show_cart_on_app" value="1" x-model="preview.showCart">
                            Tampilkan cart
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium">
                            <input type="checkbox" name="show_order_summary_on_app" value="1" x-model="preview.showOrderSummary">
                            Ringkasan order
                        </label>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Posisi cart
                            <select name="cart_position" x-model="preview.cartPosition" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium normal-case tracking-normal">
                                <option value="bottom">Bawah konten</option>
                                <option value="right">Kanan layar</option>
                            </select>
                        </label>
                        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Checkout
                            <select name="checkout_position" x-model="preview.checkoutPosition" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium normal-case tracking-normal">
                                <option value="bottom">Tombol bawah</option>
                                <option value="floating">Floating button</option>
                                <option value="cart">Di dalam cart</option>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4" x-show="preview.productCardStyle === 'image'" x-transition>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ukuran gambar</p>
                    <p class="text-xs leading-5 text-slate-500">Bagian ini masih untuk simulasi preview. Penyimpanan ukuran gambar permanen bisa kita aktifkan setelah format final dipilih.</p>
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
                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium"><input type="checkbox" name="show_sku_on_app" value="1" x-model="preview.showSku"> SKU</label>
                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium"><input type="checkbox" name="show_stock_on_app" value="1" x-model="preview.showStock"> Stok</label>
                </div>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 active:scale-[0.97]">
                    Simpan Tampilan App
                </button>
            </div>
        </form>

        <div class="mx-auto w-full self-start border border-slate-300 bg-slate-950 p-3 shadow-2xl shadow-slate-950/20 transition-all duration-300" :class="deviceFrameClass()">
            <div class="relative overflow-hidden bg-slate-50 transition-all duration-300" :class="deviceScreenClass()">
                <div class="flex items-center gap-3 px-4 py-4 text-white" :style="`background-color: ${preview.themeColor}`">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-sm font-bold shadow-sm ring-1 ring-white/10">
                        <span x-text="(preview.storeName || 'A').charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold" x-text="preview.storeName"></p>
                        <p class="truncate text-[11px] text-white/80" x-text="preview.businessType"></p>
                    </div>
                    <button type="button" class="shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold text-white/95 transition active:scale-[0.97]">Sync</button>
                    <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-white transition active:scale-[0.97]" aria-label="Printer">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h20a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    </button>
                    <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-white transition active:scale-[0.97]" aria-label="Keluar">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                    </button>
                </div>
                <div class="p-4 transition-all duration-300" :class="contentHeightClass()">
                    <div :class="productAreaClass()">
                        <div class="min-w-0">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-bold" :style="`color: ${preview.textColor}`">Produk & Layanan</p>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold text-white" :style="`background-color: ${preview.themeColor}`" x-text="preview.appLayout"></span>
                            </div>
                            <div x-show="preview.showSearch" x-transition class="mb-3 flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-400 shadow-sm">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <span>Cari produk atau layanan</span>
                            </div>
                            <template x-if="previewProducts.length === 0">
                                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 text-center transition-all duration-300" :class="emptyStateHeightClass()">
                                    <p class="text-sm font-semibold" :style="`color: ${preview.textColor}`">Belum ada item</p>
                                    <p class="mt-1 text-xs leading-5" :style="`color: ${preview.secondaryTextColor}`">Tambahkan produk aktif di halaman Katalog.</p>
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
                                                <p class="line-clamp-2 text-xs font-bold" :style="`color: ${preview.textColor}`" x-text="product.name"></p>
                                                <p class="mt-1 text-[10px]" :style="`color: ${preview.secondaryTextColor}`"><span x-text="product.type"></span><span x-show="preview.showSku && product.sku" x-text="' - ' + product.sku"></span></p>
                                                <p x-show="preview.showStock" class="mt-1 text-[10px]" :style="`color: ${preview.secondaryTextColor}`" x-text="product.stock ? 'Stok ' + product.stock : 'Tanpa stok'"></p>
                                                <p class="mt-2 text-xs font-bold" :style="`color: ${preview.priceTextColor}`"><span x-text="product.price"></span> / <span x-text="product.unit"></span></p>
                                                <div class="mt-3 flex items-center justify-between gap-2">
                                                    <button type="button" class="flex h-7 w-9 items-center justify-center rounded-full text-xs font-bold text-white transition active:scale-[0.97]" :style="`background-color: ${preview.themeColor}`" :class="qty(product) <= 0 ? 'opacity-35' : ''" @click.stop="removeQty(product)">-</button>
                                                    <button type="button" class="min-w-0 flex-1 rounded-full border border-slate-200 bg-white px-2 py-1 text-center text-[11px] font-bold text-slate-700 transition active:scale-[0.97]" @click.stop="addQty(product)">
                                                        <span x-text="formatQty(qty(product))"></span>
                                                    </button>
                                                    <button type="button" class="flex h-7 w-9 items-center justify-center rounded-full text-xs font-bold text-white transition active:scale-[0.97]" :style="`background-color: ${preview.themeColor}`" @click.stop="addQty(product)">+</button>
                                                </div>
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
                                                    <p class="truncate text-xs font-bold" :style="`color: ${preview.textColor}`" x-text="product.name"></p>
                                                    <p class="mt-0.5 text-[10px]" :style="`color: ${preview.secondaryTextColor}`"><span x-text="product.type"></span><span x-show="preview.showStock" x-text="product.stock ? ' - Stok ' + product.stock : ' - Tanpa stok'"></span></p>
                                                </div>
                                                <p class="text-right text-[11px] font-bold" :style="`color: ${preview.priceTextColor}`" x-text="product.price"></p>
                                                <div class="flex items-center gap-1">
                                                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold text-white transition active:scale-[0.97]" :style="`background-color: ${preview.themeColor}`" :class="qty(product) <= 0 ? 'opacity-35' : ''" @click.stop="removeQty(product)">-</button>
                                                    <button type="button" class="rounded-full border border-slate-200 bg-white px-2 py-1 text-[10px] font-bold text-slate-700 transition active:scale-[0.97]" @click.stop="addQty(product)" x-text="formatQty(qty(product))"></button>
                                                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold text-white transition active:scale-[0.97]" :style="`background-color: ${preview.themeColor}`" @click.stop="addQty(product)">+</button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <aside x-show="isSideCart()" x-transition class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold" :style="`color: ${preview.textColor}`">Cart</p>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold" :style="`color: ${preview.secondaryTextColor}`" x-text="selectedProduct() ? formatQty(selectedQuantity()) + ' item' : '0 item'"></span>
                            </div>
                            <div class="mt-3 rounded-xl bg-slate-50 p-3">
                                <p class="text-xs font-semibold" :style="`color: ${preview.textColor}`" x-text="selectedProduct()?.name || 'Cart kosong'"></p>
                                <p class="mt-1 text-[10px]" :style="`color: ${preview.secondaryTextColor}`" x-text="selectedProduct() ? formatQty(selectedQuantity()) + ' ' + selectedProduct().unit + ' x ' + selectedProduct().price : 'Pilih item terlebih dulu'"></p>
                            </div>
                            <div x-show="preview.showOrderSummary" class="mt-3 space-y-1 text-[10px]" :style="`color: ${preview.secondaryTextColor}`">
                                <div class="flex justify-between"><span>Subtotal</span><span x-text="formatCurrency(cartTotal() || (selectedProduct()?.priceValue || 0) * selectedQuantity())"></span></div>
                                <div class="flex justify-between"><span>Pajak</span><span x-text="formatCurrency(0)"></span></div>
                                <div class="flex justify-between font-bold" :style="`color: ${preview.priceTextColor}`"><span>Total</span><span x-text="formatCurrency(cartTotal() || (selectedProduct()?.priceValue || 0) * selectedQuantity())"></span></div>
                            </div>
                            <button x-show="cartCheckoutVisible()" class="mt-3 w-full rounded-xl py-2 text-xs font-semibold text-white" :style="`background-color: ${preview.themeColor}`">Checkout</button>
                        </aside>
                    </div>

                    <div x-show="isBottomCart()" x-transition class="mt-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-bold" :style="`color: ${preview.textColor}`">Cart</p>
                            <span class="text-xs" :style="`color: ${preview.secondaryTextColor}`" x-text="selectedProduct() ? formatQty(selectedQuantity()) + ' item' : '0 item'"></span>
                        </div>
                        <div class="mt-2 space-y-1">
                            <template x-if="selectedProduct()">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 truncate text-[11px]" :style="`color: ${preview.secondaryTextColor}`" x-text="cartLabel()"></p>
                                    <p class="shrink-0 text-xs font-bold" :style="`color: ${preview.priceTextColor}`" x-text="formatCurrency(cartTotal() || (selectedProduct()?.priceValue || 0) * selectedQuantity())"></p>
                                </div>
                            </template>
                            <template x-if="!selectedProduct()">
                                <p class="text-[11px]" :style="`color: ${preview.secondaryTextColor}`">Belum ada item dipilih</p>
                            </template>
                        </div>
                        <div x-show="preview.showOrderSummary" class="mt-3 space-y-2 text-[11px]" :style="`color: ${preview.secondaryTextColor}`">
                            <div class="flex justify-between gap-3">
                                <span>Subtotal</span>
                                <span :style="`color: ${preview.priceTextColor}`" x-text="formatCurrency(cartTotal() || (selectedProduct()?.priceValue || 0) * selectedQuantity())"></span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span>Pajak</span>
                                <span :style="`color: ${preview.priceTextColor}`" x-text="formatCurrency(0)"></span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span>Service</span>
                                <span :style="`color: ${preview.priceTextColor}`" x-text="formatCurrency(0)"></span>
                            </div>
                            <div class="flex justify-between gap-3 font-bold" :style="`color: ${preview.textColor}`">
                                <span>Total</span>
                                <span :style="`color: ${preview.priceTextColor}`" x-text="formatCurrency(cartTotal() || (selectedProduct()?.priceValue || 0) * selectedQuantity())"></span>
                            </div>
                        </div>
                        <button x-show="cartCheckoutVisible()" class="mt-3 w-full rounded-xl py-2 text-xs font-semibold text-white" :style="`background-color: ${preview.themeColor}`">Checkout</button>
                    </div>
                </div>
                <div x-show="bottomCheckoutBarVisible()" class="border-t border-slate-200 bg-white p-4">
                    <button class="w-full rounded-xl py-2.5 text-sm font-semibold text-white" :style="`background-color: ${preview.themeColor}`" x-text="checkoutLabel()"></button>
                </div>
                <button x-show="preview.checkoutPosition === 'floating'" x-transition class="absolute bottom-4 right-4 rounded-full px-5 py-3 text-sm font-semibold text-white shadow-xl shadow-slate-950/20" :style="`background-color: ${preview.themeColor}`" x-text="checkoutLabel()"></button>
            </div>
        </div>
    </section>
</x-admin.shell>
