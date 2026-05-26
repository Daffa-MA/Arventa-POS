<x-admin.shell
    :setting="$setting"
    active="settings"
    title="Setting"
    subtitle="Pengaturan toko, Web Admin, dan App Kasir dipisah agar masing-masing UI bisa dikembangkan mandiri."
>
    @php
        $logoUrl = $setting->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($setting->logo_path) : null;
        $qrUrl = $setting->qris_image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($setting->qris_image_path) : null;
    @endphp

    <form
        method="post"
        action="{{ route('admin.settings.update') }}"
        enctype="multipart/form-data"
        x-data="{
            loading: false,
            done: false,
            preview: {
                storeName: @js(old('store_name', $setting->store_name)),
                businessType: @js(old('business_type', $setting->business_type)),
                address: @js(old('address', $setting->address)),
                receiptFooter: @js(old('receipt_footer', $setting->receipt_footer)),
                logoUrl: @js($logoUrl),
                qrUrl: @js($qrUrl),
                receiptTemplate: @js(old('receipt_template', $setting->receipt_template ?? 'classic')),
                receiptPaperSize: @js(old('receipt_paper_size', $setting->receipt_paper_size ?? '58')),
                showLogo: @js((bool) old('receipt_show_logo', $setting->receipt_show_logo ?? false)),
                showAddress: @js((bool) old('receipt_show_address', $setting->receipt_show_address ?? true)),
                showDatetime: @js((bool) old('receipt_show_datetime', $setting->receipt_show_datetime ?? true)),
                showQris: @js((bool) old('receipt_show_qris', $setting->receipt_show_qris ?? false)),
                showBusinessType: @js((bool) old('receipt_show_business_type', $setting->receipt_show_business_type ?? true)),
                showPaymentMethod: @js((bool) old('receipt_show_payment_method', $setting->receipt_show_payment_method ?? true)),
                showItemPrice: @js((bool) old('receipt_show_item_price', $setting->receipt_show_item_price ?? true)),
                taxRate: @js((string) old('tax_rate', $setting->tax_rate)),
                serviceRate: @js((string) old('service_charge_rate', $setting->service_charge_rate)),
                currency: @js(old('currency', $setting->currency)),
            },
            receiptSubtotal() {
                return 55000
            },
            receiptTax() {
                return Math.round(this.receiptSubtotal() * Number(this.preview.taxRate || 0) / 100)
            },
            receiptService() {
                return Math.round(this.receiptSubtotal() * Number(this.preview.serviceRate || 0) / 100)
            },
            receiptTotal() {
                return this.receiptSubtotal() + this.receiptTax() + this.receiptService()
            },
            money(value) {
                const currency = this.preview.currency || 'IDR'
                const number = Number(value || 0).toLocaleString('id-ID')
                return currency === 'IDR' ? `Rp${number}` : `${currency} ${number}`
            },
            templateLabel() {
                if (this.preview.receiptTemplate === 'compact') return 'Ringkas'
                if (this.preview.receiptTemplate === 'detailed') return 'Detail'
                return 'Classic'
            }
        }"
        @submit="loading = true; setTimeout(() => { loading = false; done = true; setTimeout(() => done = false, 2000) }, 700)"
        class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-950/[0.03]"
    >
        @csrf
        @method('put')

        <div class="grid gap-6 xl:grid-cols-2">
            <fieldset class="grid gap-4">
                <legend class="text-lg font-semibold text-slate-950">Identitas Toko</legend>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama toko</label>
                    <input name="store_name" x-model="preview.storeName" value="{{ old('store_name', $setting->store_name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-[var(--accent)] focus:ring-4 focus:ring-blue-500/10">
                    @error('store_name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis usaha</label>
                    <input name="business_type" x-model="preview.businessType" value="{{ old('business_type', $setting->business_type) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-[var(--accent)] focus:ring-4 focus:ring-blue-500/10">
                    @error('business_type')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-950 text-sm font-bold text-white">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo {{ $setting->store_name }}" class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(mb_substr($setting->admin_brand_name ?? $setting->store_name, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-950">Logo toko</p>
                            <p class="text-xs leading-5 text-slate-500">PNG, JPG, WebP, atau SVG. Maksimal 2MB.</p>
                        </div>
                    </div>
                    <input name="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="preview.logoUrl = $event.target.files?.[0] ? URL.createObjectURL($event.target.files[0]) : preview.logoUrl" class="w-full rounded-xl border border-dashed border-slate-300 bg-white px-3 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                    @error('logo')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</label>
                    <input name="address" x-model="preview.address" value="{{ old('address', $setting->address) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-[var(--accent)] focus:ring-4 focus:ring-blue-500/10">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Footer struk</label>
                    <input name="receipt_footer" x-model="preview.receiptFooter" value="{{ old('receipt_footer', $setting->receipt_footer) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-[var(--accent)] focus:ring-4 focus:ring-blue-500/10">
                </div>
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-950">Template Struk</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Model struk</label>
                            <select name="receipt_template" x-model="preview.receiptTemplate" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                                @foreach (['classic' => 'Classic', 'compact' => 'Ringkas', 'detailed' => 'Detail'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('receipt_template', $setting->receipt_template ?? 'classic') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kertas printer</label>
                            <select name="receipt_paper_size" x-model="preview.receiptPaperSize" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                                @foreach (['58' => '58mm', '80' => '80mm'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('receipt_paper_size', $setting->receipt_paper_size ?? '58') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="receipt_show_logo" value="1" x-model="preview.showLogo" @checked(old('receipt_show_logo', $setting->receipt_show_logo ?? false)) class="rounded border-slate-300 text-slate-950">
                            Logo
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="receipt_show_address" value="1" x-model="preview.showAddress" @checked(old('receipt_show_address', $setting->receipt_show_address ?? true)) class="rounded border-slate-300 text-slate-950">
                            Alamat
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="receipt_show_datetime" value="1" x-model="preview.showDatetime" @checked(old('receipt_show_datetime', $setting->receipt_show_datetime ?? true)) class="rounded border-slate-300 text-slate-950">
                            Tanggal & jam
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="receipt_show_qris" value="1" x-model="preview.showQris" @checked(old('receipt_show_qris', $setting->receipt_show_qris ?? false)) class="rounded border-slate-300 text-slate-950">
                            QR
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="receipt_show_business_type" value="1" x-model="preview.showBusinessType" @checked(old('receipt_show_business_type', $setting->receipt_show_business_type ?? true)) class="rounded border-slate-300 text-slate-950">
                            Jenis usaha
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="receipt_show_payment_method" value="1" x-model="preview.showPaymentMethod" @checked(old('receipt_show_payment_method', $setting->receipt_show_payment_method ?? true)) class="rounded border-slate-300 text-slate-950">
                            Metode bayar
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="receipt_show_item_price" value="1" x-model="preview.showItemPrice" @checked(old('receipt_show_item_price', $setting->receipt_show_item_price ?? true)) class="rounded border-slate-300 text-slate-950">
                            Harga item
                        </label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <legend class="px-1 text-lg font-semibold text-slate-950">Tampilan Web Admin</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama di sidebar</label>
                        <input name="admin_brand_name" value="{{ old('admin_brand_name', $setting->admin_brand_name ?? 'Arventa POS') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                        @error('admin_brand_name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Label console</label>
                        <input name="admin_console_label" value="{{ old('admin_console_label', $setting->admin_console_label ?? 'Admin Console') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                        @error('admin_console_label')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Warna admin</label>
                        <input name="admin_theme_color" x-model="admin.themeColor" type="color" value="{{ old('admin_theme_color', $setting->admin_theme_color ?? '#0F172A') }}" class="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-2 py-1">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sidebar</label>
                        <select name="admin_sidebar_style" x-model="admin.sidebarStyle" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                            @foreach (['light' => 'Light', 'dark' => 'Dark', 'accent' => 'Accent color'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('admin_sidebar_style', $setting->admin_sidebar_style ?? 'light') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Density</label>
                        <select name="admin_density" x-model="admin.density" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                            @foreach (['comfortable' => 'Comfortable', 'compact' => 'Compact'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('admin_density', $setting->admin_density ?? 'comfortable') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 xl:sticky xl:top-24 xl:self-start">
                <legend class="px-1 text-lg font-semibold text-slate-950">Preview Struk</legend>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-950" x-text="templateLabel()"></p>
                        <p class="text-xs text-slate-500" x-text="preview.receiptPaperSize + 'mm thermal'"></p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200" x-text="preview.currency || 'IDR'"></span>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 p-4">
                    <div class="mx-auto rounded-sm bg-white px-4 py-5 font-mono text-[11px] leading-5 text-slate-950 shadow-xl shadow-slate-950/10 transition-all"
                        :class="preview.receiptPaperSize === '80' ? 'w-full max-w-[360px]' : 'w-full max-w-[260px]'"
                    >
                        <div class="text-center">
                            <div x-show="preview.showLogo" class="mb-2 flex justify-center">
                                <template x-if="preview.logoUrl">
                                    <img :src="preview.logoUrl" alt="" class="h-12 w-12 rounded-sm object-contain">
                                </template>
                                <template x-if="!preview.logoUrl">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-sm border border-dashed border-slate-300 text-[9px] font-bold text-slate-400">LOGO</div>
                                </template>
                            </div>
                            <p class="text-[13px] font-bold uppercase tracking-wide" x-text="preview.storeName || 'Nama Toko'"></p>
                            <p x-show="preview.showBusinessType && preview.receiptTemplate !== 'compact'" class="text-slate-600" x-text="preview.businessType || 'Jenis Usaha'"></p>
                            <p x-show="preview.showAddress && preview.address" class="mt-1 text-slate-600" x-text="preview.address"></p>
                        </div>

                        <div class="my-3 border-t border-dashed border-slate-400"></div>

                        <div class="space-y-0.5">
                            <div class="flex justify-between gap-3">
                                <span>Invoice</span>
                                <span>ARV-0001</span>
                            </div>
                            <div x-show="preview.showDatetime" class="flex justify-between gap-3">
                                <span>Tanggal</span>
                                <span>26/05/2026</span>
                            </div>
                            <div x-show="preview.showDatetime && preview.receiptTemplate !== 'compact'" class="flex justify-between gap-3">
                                <span>Jam</span>
                                <span>14:30</span>
                            </div>
                            <div x-show="preview.showPaymentMethod && preview.receiptTemplate !== 'compact'" class="flex justify-between gap-3">
                                <span>Bayar</span>
                                <span>Tunai</span>
                            </div>
                        </div>

                        <div class="my-3 border-t border-dashed border-slate-400"></div>

                        <div class="space-y-1">
                            <div>
                                <div class="flex justify-between gap-3">
                                    <span class="truncate">2 pcs x Parfum</span>
                                    <span x-show="preview.showItemPrice" class="shrink-0" x-text="money(50000)"></span>
                                </div>
                                <p x-show="preview.receiptTemplate === 'detailed'" class="text-slate-500">Harga satuan: <span x-text="money(25000)"></span></p>
                            </div>
                            <div>
                                <div class="flex justify-between gap-3">
                                    <span class="truncate">1 pcs x Botol</span>
                                    <span x-show="preview.showItemPrice" class="shrink-0" x-text="money(5000)"></span>
                                </div>
                                <p x-show="preview.receiptTemplate === 'detailed'" class="text-slate-500">Harga satuan: <span x-text="money(5000)"></span></p>
                            </div>
                        </div>

                        <div class="my-3 border-t border-dashed border-slate-400"></div>

                        <div class="space-y-0.5">
                            <div class="flex justify-between gap-3">
                                <span>Subtotal</span>
                                <span x-text="money(receiptSubtotal())"></span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span>Pajak</span>
                                <span x-text="money(receiptTax())"></span>
                            </div>
                            <div x-show="receiptService() > 0 || preview.receiptTemplate !== 'compact'" class="flex justify-between gap-3">
                                <span>Service</span>
                                <span x-text="money(receiptService())"></span>
                            </div>
                            <div class="flex justify-between gap-3 pt-1 text-[13px] font-bold">
                                <span>Total</span>
                                <span x-text="money(receiptTotal())"></span>
                            </div>
                            <div x-show="preview.receiptTemplate === 'detailed'" class="flex justify-between gap-3">
                                <span>Diterima</span>
                                <span x-text="money(receiptTotal())"></span>
                            </div>
                            <div x-show="preview.receiptTemplate === 'detailed'" class="flex justify-between gap-3">
                                <span>Kembali</span>
                                <span x-text="money(0)"></span>
                            </div>
                        </div>

                        <div x-show="preview.showQris" class="pt-3">
                            <div class="my-3 border-t border-dashed border-slate-400"></div>
                            <div class="flex justify-center">
                                <template x-if="preview.qrUrl">
                                    <img :src="preview.qrUrl" alt="" class="h-24 w-24 object-contain">
                                </template>
                                <template x-if="!preview.qrUrl">
                                    <div class="grid h-24 w-24 grid-cols-4 gap-1 rounded-sm border border-slate-300 bg-white p-2">
                                        <span class="rounded-sm bg-slate-950"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-950"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-950"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-950"></span>
                                        <span class="rounded-sm bg-slate-950"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-950"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-950"></span>
                                        <span class="rounded-sm bg-slate-300"></span>
                                        <span class="rounded-sm bg-slate-950"></span>
                                    </div>
                                </template>
                            </div>
                            <p class="mt-1 text-center text-slate-500">QR</p>
                        </div>

                        <div class="my-3 border-t border-dashed border-slate-400"></div>

                        <p class="text-center text-slate-600" x-text="preview.receiptFooter || 'Terima kasih.'"></p>
                    </div>
                </div>
            </fieldset>

            <fieldset class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <legend class="px-1 text-lg font-semibold text-slate-950">Biaya dan Struk</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pajak %</label>
                        <input name="tax_rate" x-model="preview.taxRate" type="number" step="0.01" value="{{ old('tax_rate', $setting->tax_rate) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Service %</label>
                        <input name="service_charge_rate" x-model="preview.serviceRate" type="number" step="0.01" value="{{ old('service_charge_rate', $setting->service_charge_rate) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mata uang</label>
                        <input name="currency" x-model="preview.currency" value="{{ old('currency', $setting->currency) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                    </div>
                </div>
                <div class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 lg:grid-cols-[160px_1fr]">
                    <div class="flex h-40 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50">
                        @if ($qrUrl)
                            <img src="{{ $qrUrl }}" alt="QR {{ $setting->store_name }}" class="h-full w-full object-contain p-2">
                        @else
                            <div class="text-center">
                                <svg class="mx-auto h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                                    <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                                    <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                                    <path d="M14 14h2v2h-2zM19 14h2v2h-2zM14 19h2v2h-2zM19 19h2v2h-2z"></path>
                                </svg>
                                <p class="mt-2 text-xs font-medium text-slate-500">Belum ada QR</p>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-col justify-center gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">QR Toko</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Upload QR pembayaran, website, katalog, atau link lain milik toko.</p>
                        </div>
                        <input name="qris_image" type="file" accept="image/png,image/jpeg,image/webp" @change="preview.qrUrl = $event.target.files?.[0] ? URL.createObjectURL($event.target.files[0]) : preview.qrUrl" class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                        @error('qris_image')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </fieldset>

        </div>

        <button class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.97]" type="submit">
            <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/></svg>
            <span x-text="loading ? 'Menyimpan...' : (done ? 'Tersimpan' : 'Simpan Setting')">Simpan Setting</span>
        </button>
    </form>
</x-admin.shell>
