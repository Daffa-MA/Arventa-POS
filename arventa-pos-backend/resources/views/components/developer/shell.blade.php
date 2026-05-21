@props([
    'setting',
    'active' => 'pos-list',
    'title' => 'Developer Dashboard',
    'subtitle' => null,
])

@php
    $firstError = $errors->first();
@endphp

<x-layouts.admin title="Arventa POS Developer">
    <div
        x-data="{
            toastOpen: {{ session('status') || $errors->any() ? 'true' : 'false' }},
            toastType: {{ $errors->any() ? "'error'" : "'success'" }},
            toastMessage: @js($firstError ?: session('status')),
            init() {
                if (this.toastOpen) setTimeout(() => this.toastOpen = false, 4200)
            }
        }"
        class="min-h-screen bg-slate-50 text-slate-800"
    >
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-[1500px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-sm font-bold text-white shadow-sm">A</div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Arventa Developer</p>
                        <h1 class="truncate text-lg font-semibold text-slate-950 sm:text-xl">{{ $title }}</h1>
                    </div>
                </div>

                <span class="rounded-full bg-slate-950 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white shadow-sm">Vendor Console</span>
            </div>
        </header>

        <main class="mx-auto flex max-w-[1500px] flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            @if ($subtitle)
                <section class="animate-[fade-up_300ms_ease-out_both] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-950/[0.03]">
                    <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-sky-600">{{ $title }}</p>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $subtitle }}</p>
                        </div>
                        <div class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Single-tenant generator
                        </div>
                    </div>
                </section>
            @endif

            {{ $slot }}
        </main>

        <div x-cloak x-show="toastOpen" x-transition class="fixed bottom-5 right-5 z-50 w-[calc(100%-2.5rem)] max-w-sm rounded-2xl border bg-white p-4 text-slate-800 shadow-2xl shadow-slate-950/10" :class="toastType === 'error' ? 'border-red-200' : 'border-emerald-200'">
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
