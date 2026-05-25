@php
    $statusStyles = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'inactive' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'suspended' => 'bg-red-50 text-red-700 ring-red-200',
    ];
    $deploymentStyles = [
        'pending' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'deploying' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'deployed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'failed' => 'bg-red-50 text-red-700 ring-red-200',
    ];
@endphp

<x-developer.shell
    :setting="$setting"
    active="pos-list"
    title="POS List"
    subtitle="Dashboard developer untuk generate tenant POS pembeli. Semua toko berjalan di satu aplikasi, domain pembeli diarahkan ke app yang sama, dan data dipisah dengan pos_instance_id."
>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="animate-[fade-up_300ms_ease-out_60ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Total POS</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-950 text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $stats['total'] }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_120ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <p class="text-sm font-medium text-slate-500">Aktif</p>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $stats['active'] }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_180ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <p class="text-sm font-medium text-slate-500">Draft</p>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $stats['pending'] }}</p>
        </div>
        <div class="animate-[fade-up_300ms_ease-out_240ms_both] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <p class="text-sm font-medium text-slate-500">Deploying</p>
            <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $stats['deploying'] }}</p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.78fr_1.22fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-950/[0.03]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Generate POS Pembeli</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Isi data pembeli. Field teknis boleh dikosongkan agar sistem generate otomatis.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Multi tenant</span>
            </div>

            <form
                method="post"
                action="{{ route('developer.pos.store') }}"
                class="mt-6 grid gap-4"
                x-data="{ submitting: false }"
                @submit="submitting = true"
            >
                @csrf

                <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Nama toko
                    <input name="store_name" value="{{ old('store_name') }}" required placeholder="Contoh: Parfume POS" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                    @error('store_name') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Nama pembeli
                        <input name="buyer_name" value="{{ old('buyer_name') }}" required placeholder="Owner toko" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                        @error('buyer_name') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Kontak
                        <input name="contact" value="{{ old('contact') }}" required placeholder="WhatsApp / telepon" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                        @error('contact') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Opsional teknis</p>
                    <div class="mt-4 grid gap-4">
                        <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Subdomain
                            <input name="subdomain" value="{{ old('subdomain') }}" placeholder="parfume-pos" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                            @error('subdomain') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Domain
                            <input name="domain" value="{{ old('domain') }}" placeholder="parfume.arventa.my.id" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                            @error('domain') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Tenant key
                                <input name="database_name" value="{{ old('database_name') }}" placeholder="arventa_pos_parfume" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                                @error('database_name') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Package app
                                <input name="package_name" value="{{ old('package_name') }}" placeholder="com.arventapos.parfume" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                                @error('package_name') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Admin username
                        <input name="admin_username" value="{{ old('admin_username') }}" placeholder="admin_parfume" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                        @error('admin_username') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Admin password
                        <input name="admin_password" value="{{ old('admin_password') }}" placeholder="Kosongkan untuk auto" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                        @error('admin_password') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 active:scale-[0.97]" :disabled="submitting">
                    <svg x-show="submitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                    <span x-text="submitting ? 'Generate...' : 'Generate POS'"></span>
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-950/[0.03]">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Daftar POS Pembeli</h2>
                    <p class="text-sm text-slate-500">Domain, tenant key, dan akun admin untuk tiap pembeli.</p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $instances->count() }} instance</span>
            </div>

            @if ($instances->isEmpty())
                <div class="grid place-items-center px-6 py-16 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                    </div>
                    <p class="mt-4 font-semibold text-slate-950">Belum ada POS</p>
                    <p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">Generate POS pertama dari form di kiri. Deploy akan mengarahkan domain ke aplikasi Arventa yang sama.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Toko</th>
                                <th class="px-5 py-3">Domain</th>
                                <th class="px-5 py-3">Tenant</th>
                                <th class="px-5 py-3">Admin</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Dibuat</th>
                                <th class="px-5 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($instances as $instance)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="px-5 py-4 align-top">
                                        <p class="font-semibold text-slate-950">{{ $instance->store_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $instance->buyer_name ?: $instance->owner_name ?: 'Tanpa nama pembeli' }}{{ ($instance->contact ?: $instance->owner_phone) ? ' - '.($instance->contact ?: $instance->owner_phone) : '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <p class="font-medium text-slate-900">{{ $instance->domain }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $instance->subdomain }}</p>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <code class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $instance->database_name }}</code>
                                        <p class="mt-2 text-xs text-slate-500">{{ $instance->package_name ?: $instance->app_package_name }}</p>
                                    </td>
                                    <td class="px-5 py-4 align-top" x-data="{ showPassword: false, copied: false, password: @js($instance->admin_password) }">
                                        <p class="max-w-[150px] truncate font-medium text-slate-900">{{ $instance->admin_username }}</p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <p class="max-w-[150px] truncate font-mono text-xs text-slate-500" x-text="showPassword ? password : '**********'"></p>
                                            <button type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-950" @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                                            <button type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-950" @click="navigator.clipboard.writeText(password); copied = true; setTimeout(() => copied = false, 1500)" x-text="copied ? 'Copied' : 'Copy'"></button>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <form method="post" action="{{ route('developer.pos.status', $instance) }}" x-data="{ saving: false }" @submit="saving = true">
                                            @csrf
                                            @method('patch')
                                            <select name="status" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700" onchange="this.form.requestSubmit()">
                                                @foreach (['pending', 'active', 'inactive', 'suspended'] as $status)
                                                    <option value="{{ $status }}" @selected($instance->status === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <span x-show="saving" class="ml-2 text-xs font-medium text-slate-500">Saving...</span>
                                        </form>
                                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $deploymentStyles[$instance->deployment_status] ?? $deploymentStyles['pending'] }}">{{ ucfirst($instance->deployment_status ?? 'pending') }}</span>
                                        @if ($instance->deployment_error)
                                            <p class="mt-2 max-w-[180px] text-xs text-red-600">{{ $instance->deployment_error }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 align-top text-xs text-slate-500">{{ $instance->created_at?->format('d M Y H:i') }}</td>
                                    <td class="px-5 py-4 align-top">
                                        <div class="flex flex-wrap items-center gap-2" x-data="{ open: false, deploying: false }">
                                            <form method="post" action="{{ route('developer.pos.deploy', $instance) }}" @submit="deploying = true">
                                                @csrf
                                                <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 active:scale-[0.97]" :disabled="deploying">
                                                    <svg x-show="deploying" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                                                    <span x-text="deploying ? 'Deploying...' : 'Deploy'"></span>
                                                </button>
                                            </form>
                                            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 active:scale-[0.97]" @click="open = !open">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                                                Notes
                                            </button>
                                            <div x-cloak x-show="open" x-transition class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-950 p-4 text-xs text-slate-100 shadow-xl">
                                                <pre class="whitespace-pre-wrap font-mono leading-5">{{ $instance->deployment_notes }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</x-developer.shell>
