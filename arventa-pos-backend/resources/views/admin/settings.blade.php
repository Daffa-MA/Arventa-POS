<x-admin.shell
    :setting="$setting"
    active="settings"
    title="Setting"
    subtitle="Pengaturan toko, Web Admin, dan App Kasir dipisah agar masing-masing UI bisa dikembangkan mandiri."
>
    <form
        method="post"
        action="{{ route('admin.settings.update') }}"
        x-data="{
            loading: false,
            done: false,
            preview: {
                storeName: @js(old('store_name', $setting->store_name)),
                themeColor: @js(old('theme_color', $setting->theme_color)),
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
                    <input name="business_type" value="{{ old('business_type', $setting->business_type) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-[var(--accent)] focus:ring-4 focus:ring-blue-500/10">
                    @error('business_type')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</label>
                    <input name="address" value="{{ old('address', $setting->address) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-[var(--accent)] focus:ring-4 focus:ring-blue-500/10">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Footer struk</label>
                    <input name="receipt_footer" value="{{ old('receipt_footer', $setting->receipt_footer) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-[var(--accent)] focus:ring-4 focus:ring-blue-500/10">
                </div>
            </fieldset>

            <fieldset class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <legend class="px-1 text-lg font-semibold text-slate-950">Tampilan Web Admin</legend>
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

            <fieldset class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <legend class="px-1 text-lg font-semibold text-slate-950">Biaya dan Struk</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pajak %</label>
                        <input name="tax_rate" type="number" step="0.01" value="{{ old('tax_rate', $setting->tax_rate) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Service %</label>
                        <input name="service_charge_rate" type="number" step="0.01" value="{{ old('service_charge_rate', $setting->service_charge_rate) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mata uang</label>
                        <input name="currency" value="{{ old('currency', $setting->currency) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                    </div>
                </div>
            </fieldset>

            <fieldset class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <legend class="px-1 text-lg font-semibold text-slate-950">Tampilan App Kasir</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Warna app</label>
                        <input name="theme_color" x-model="preview.themeColor" type="color" value="{{ old('theme_color', $setting->theme_color) }}" class="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-2 py-1">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Layout produk</label>
                        <select name="app_layout" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                            @foreach (['grid' => 'Grid kartu', 'list' => 'List detail', 'compact' => 'Compact cepat'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('app_layout', $setting->app_layout) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gaya kartu</label>
                        <select name="product_card_style" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium">
                            @foreach (['minimal' => 'Minimal', 'image' => 'Dengan area gambar'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('product_card_style', $setting->product_card_style) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="show_sku_on_app" value="1" class="rounded border-slate-300 text-blue-600" @checked(old('show_sku_on_app', $setting->show_sku_on_app))>
                            SKU
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="show_stock_on_app" value="1" class="rounded border-slate-300 text-blue-600" @checked(old('show_stock_on_app', $setting->show_stock_on_app))>
                            Stok
                        </label>
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
