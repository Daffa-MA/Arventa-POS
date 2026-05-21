<x-admin.shell
    :setting="$setting"
    active="settings"
    title="Setting"
    subtitle="Pengaturan toko, Web Admin, dan App Kasir dipisah agar masing-masing UI bisa dikembangkan mandiri."
>
    <form
        method="post"
        action="{{ route('admin.settings.update') }}"
        enctype="multipart/form-data"
        x-data="{
            loading: false,
            done: false,
            preview: {
                storeName: @js(old('store_name', $setting->store_name)),
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
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-950 text-sm font-bold text-white">
                            @if ($setting->logo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($setting->logo_path) }}" alt="Logo {{ $setting->store_name }}" class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(mb_substr($setting->admin_brand_name ?? $setting->store_name, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-950">Logo POS Admin</p>
                            <p class="text-xs leading-5 text-slate-500">PNG, JPG, WebP, atau SVG. Maksimal 2MB.</p>
                        </div>
                    </div>
                    <input name="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="w-full rounded-xl border border-dashed border-slate-300 bg-white px-3 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                    @error('logo')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
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
                <div class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 lg:grid-cols-[160px_1fr]">
                    <div class="flex h-40 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50">
                        @if ($setting->qris_image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($setting->qris_image_path) }}" alt="QRIS {{ $setting->store_name }}" class="h-full w-full object-contain p-2">
                        @else
                            <div class="text-center">
                                <svg class="mx-auto h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                                    <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                                    <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                                    <path d="M14 14h2v2h-2zM19 14h2v2h-2zM14 19h2v2h-2zM19 19h2v2h-2z"></path>
                                </svg>
                                <p class="mt-2 text-xs font-medium text-slate-500">Belum ada QRIS</p>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-col justify-center gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">Pembayaran QRIS</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Upload gambar QRIS toko. App kasir akan menampilkannya saat kasir memilih metode QRIS di checkout.</p>
                        </div>
                        <input name="qris_image" type="file" accept="image/png,image/jpeg,image/webp" class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
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
