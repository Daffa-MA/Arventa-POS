@props([
    'setting',
    'active' => 'dashboard',
    'title' => 'Dashboard',
    'subtitle' => null,
])

@php
    $adminPrimary = $setting->admin_theme_color ?? '#0F172A';
    $appPrimary = $setting->theme_color;
    $adminBrandName = $setting->admin_brand_name ?: 'Arventa POS';
    $adminConsoleLabel = $setting->admin_console_label ?: 'Admin Console';
    $logoUrl = $setting->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($setting->logo_path) : null;
    $appLogoUrl = asset('arventa-logo.png');
    $firstError = $errors->first();
    $currentAdmin = $currentAdmin ?? null;
    $navItems = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'layout-dashboard'],
        ['key' => 'settings', 'label' => 'Setting', 'href' => route('admin.settings'), 'icon' => 'settings'],
        ['key' => 'products', 'label' => 'Katalog', 'href' => route('admin.products'), 'icon' => 'boxes'],
        ['key' => 'app-preview', 'label' => 'Tampilan App', 'href' => route('admin.app-preview'), 'icon' => 'smartphone'],
        ['key' => 'devices', 'label' => 'Perangkat Kasir', 'href' => route('admin.devices'), 'icon' => 'qr'],
        ['key' => 'transactions', 'label' => 'Transaksi', 'href' => route('admin.transactions'), 'icon' => 'receipt'],
    ];
@endphp

<x-layouts.admin :title="$adminBrandName.' Admin'">
    <div
        x-data="{
            sidebarOpen: false,
            toastOpen: {{ session('status') || $errors->any() ? 'true' : 'false' }},
            toastType: {{ $errors->any() ? "'error'" : "'success'" }},
            toastMessage: @js($firstError ?: session('status')),
            admin: {
                themeColor: @js(old('admin_theme_color', $adminPrimary)),
                sidebarStyle: @js(old('admin_sidebar_style', $setting->admin_sidebar_style ?? 'light')),
                density: @js(old('admin_density', $setting->admin_density ?? 'comfortable')),
            },
            init() {
                if (this.toastOpen) setTimeout(() => this.toastOpen = false, 4200)
            }
        }"
        class="min-h-screen bg-slate-50 text-slate-800"
        style="--accent: {{ $adminPrimary }}; --app-accent: {{ $appPrimary }};"
        :style="`--accent: ${admin.themeColor}; --app-accent: '{{ $appPrimary }}'`"
    >
        <aside x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/30 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></aside>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r shadow-xl shadow-slate-950/5 backdrop-blur-xl transition-transform duration-300 lg:translate-x-0"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                admin.sidebarStyle === 'dark' ? 'border-slate-800 bg-slate-950 text-white' : (admin.sidebarStyle === 'accent' ? 'border-transparent text-white' : 'border-slate-200/80 bg-white/95 text-slate-950')
            ]"
            :style="admin.sidebarStyle === 'accent' ? `background-color: ${admin.themeColor}` : ''"
        >
            <div class="flex h-16 items-center gap-3 border-b border-slate-200/70 px-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/10 shadow-sm shadow-slate-950/20" style="background-color: var(--accent)">
                    <img src="{{ $appLogoUrl }}" alt="Arventa POS logo" class="h-full w-full object-contain p-1.5">
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold" :class="admin.sidebarStyle === 'light' ? 'text-slate-950' : 'text-white'">{{ $adminBrandName }}</p>
                    <p class="text-xs" :class="admin.sidebarStyle === 'light' ? 'text-slate-500' : 'text-white/65'">{{ $adminConsoleLabel }}</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                @foreach ($navItems as $item)
                    @php($isActive = $active === $item['key'])
                    <a
                        href="{{ $item['href'] }}"
                        @click="sidebarOpen = false"
                        class="group flex items-center gap-3 rounded-lg px-3 text-sm font-medium transition duration-200 active:scale-[0.97] {{ $isActive ? 'text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}"
                        :class="[
                            admin.density === 'compact' ? 'py-2' : 'py-2.5',
                            admin.sidebarStyle === 'light' ? '' : '{{ $isActive ? 'bg-white/15 text-white shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}'
                        ]"
                        @if ($isActive)
                            :style="admin.sidebarStyle === 'accent' ? '' : `background-color: ${admin.themeColor}`"
                        @endif
                    >
                        @if ($item['icon'] === 'layout-dashboard')
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        @elseif ($item['icon'] === 'settings')
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.51a2 2 0 0 1 1-1.72l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2Z"/><circle cx="12" cy="12" r="3"/></svg>
                        @elseif ($item['icon'] === 'boxes')
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        @elseif ($item['icon'] === 'receipt')
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/><path d="M10 16H8"/></svg>
                        @elseif ($item['icon'] === 'qr')
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M16 16h.01"/><path d="M21 16h-2"/><path d="M16 21v-2"/><path d="M21 21h-5v-5"/></svg>
                        @else
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/></svg>
                        @endif
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="m-3 rounded-xl border p-4" :class="admin.sidebarStyle === 'light' ? 'border-slate-200 bg-slate-50' : 'border-white/10 bg-white/10'">
                <p class="text-xs font-medium uppercase tracking-wide" :class="admin.sidebarStyle === 'light' ? 'text-slate-500' : 'text-white/65'">Tema Web Admin</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="h-9 w-9 rounded-lg shadow-inner" style="background-color: var(--accent)"></span>
                    <div>
                        <p class="text-sm font-semibold" :class="admin.sidebarStyle === 'light' ? 'text-slate-900' : 'text-white'" x-text="admin.themeColor"></p>
                        <p class="text-xs" :class="admin.sidebarStyle === 'light' ? 'text-slate-500' : 'text-white/65'" x-text="admin.sidebarStyle + ' sidebar'"></p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl">
                <div class="mx-auto flex h-16 max-w-[1500px] items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 active:scale-[0.97] lg:hidden" @click="sidebarOpen = true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
                        </button>
                        <div class="hidden h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 shadow-sm shadow-slate-950/10 sm:flex" style="background-color: var(--accent)">
                            <img src="{{ $appLogoUrl }}" alt="Arventa POS logo" class="h-full w-full object-contain p-1.5">
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">{{ $adminBrandName }}</p>
                                <span class="hidden rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:inline-flex">Tenant</span>
                            </div>
                            <h1 class="text-lg font-semibold text-slate-950 sm:text-xl">{{ $title }}</h1>
                        </div>
                    </div>
                    <div class="hidden items-center gap-2 sm:flex">
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 shadow-sm">
                            @if ($logoUrl)
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100">
                                    <img src="{{ $logoUrl }}" alt="{{ $setting->store_name }} logo" class="h-full w-full object-contain p-0.5">
                                </span>
                            @else
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: var(--accent)"></span>
                            @endif
                            {{ $currentAdmin?->name ?? $setting->store_name }}
                        </div>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            <div class="mx-auto flex max-w-[1500px] flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                @if ($subtitle)
                    <div class="animate-[fade-up_300ms_ease-out_both] rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-950/[0.03]">
                        <p class="text-sm font-medium" style="color: var(--accent)">{{ $title }}</p>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $subtitle }}</p>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>

        <div x-cloak x-show="toastOpen" x-transition class="fixed bottom-5 right-5 z-50 w-[calc(100%-2.5rem)] max-w-sm rounded-2xl border bg-white p-4 shadow-2xl shadow-slate-950/10" :class="toastType === 'error' ? 'border-red-200' : 'border-emerald-200'">
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="toastType === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'">
                    <svg x-show="toastType === 'error'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <svg x-show="toastType !== 'error'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-950" x-text="toastType === 'error' ? 'Validasi gagal' : 'Berhasil'"></p>
                    <p class="mt-0.5 text-sm text-slate-600" x-text="toastMessage"></p>
                </div>
                <button type="button" class="text-slate-400 transition hover:text-slate-700" @click="toastOpen = false">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </div>

        <style>
            @keyframes fade-up {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </div>
</x-layouts.admin>
