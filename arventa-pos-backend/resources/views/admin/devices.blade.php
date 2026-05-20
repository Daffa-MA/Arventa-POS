<x-admin.shell
    :setting="$setting"
    active="devices"
    title="Perangkat Kasir"
    subtitle="Hubungkan banyak perangkat kasir Android dengan QR pairing. Setiap kode hanya bisa dipakai satu kali dan kedaluwarsa dalam 10 menit."
>
    <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Hubungkan Perangkat</h2>
            <form method="post" action="{{ route('admin.devices.pairing-codes.store') }}" class="mt-6 grid gap-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama kasir</label>
                    <input name="cashier_name" value="{{ old('cashier_name') }}" placeholder="Contoh: Kasir Shift Pagi" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                    @error('cashier_name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Label perangkat opsional</label>
                    <input name="device_label" value="{{ old('device_label') }}" placeholder="Contoh: Tablet Kasir 1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium">
                </div>
                <button class="rounded-xl px-4 py-3 text-sm font-semibold text-white transition hover:brightness-95 active:scale-[0.97]" style="background-color: var(--accent)">Generate QR Pairing</button>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Kode Pairing Aktif</h2>
                    <p class="mt-1 text-sm text-slate-500">Scan QR dari app kasir atau input kode 6 digit.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @forelse ($pairingCodes as $pairing)
                    @php
                        $payload = json_encode([
                            'type' => 'arventa_pairing',
                            'code' => $pairing->code,
                            'api_url' => url('/api/pairing/connect'),
                        ]);
                        $isExpired = $pairing->expires_at->isPast();
                    @endphp
                    <div
                        x-data="{ qrPayload: @js($payload), qrId: 'qr-{{ $pairing->id }}' }"
                        x-init="$nextTick(() => window.QRCode && new QRCode(document.getElementById(qrId), { text: qrPayload, width: 148, height: 148 }))"
                        class="rounded-2xl border {{ $pairing->paired_at || $isExpired ? 'border-slate-200 bg-slate-50 opacity-70' : 'border-blue-100 bg-white' }} p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $pairing->cashier_name }}</p>
                                <p class="text-xs text-slate-500">{{ $pairing->device_label ?: 'Tanpa label perangkat' }}</p>
                            </div>
                            @if ($pairing->paired_at)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Terhubung</span>
                            @elseif ($isExpired)
                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Expired</span>
                            @else
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Aktif</span>
                            @endif
                        </div>
                        <div class="mt-4 flex items-center gap-4">
                            <div id="qr-{{ $pairing->id }}" class="flex h-[148px] w-[148px] items-center justify-center rounded-xl bg-white p-2 ring-1 ring-slate-200"></div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</p>
                                <p class="mt-1 font-mono text-3xl font-bold tracking-widest text-slate-950">{{ $pairing->code }}</p>
                                <p class="mt-2 text-xs text-slate-500">Berlaku sampai {{ $pairing->expires_at->format('H:i') }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                        <p class="font-semibold text-slate-950">Belum ada kode pairing</p>
                        <p class="mt-1 text-sm text-slate-500">Generate kode untuk mulai menghubungkan perangkat kasir.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-950">Perangkat Terhubung</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($devices as $device)
                <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-slate-950">{{ $device->device_name }}</p>
                        <p class="text-sm text-slate-500">{{ $device->user->name }} · paired {{ $device->paired_at->format('d M Y H:i') }}</p>
                        <p class="text-xs text-slate-400">Last seen: {{ $device->last_seen_at?->format('d M Y H:i') ?? '-' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($device->revoked_at)
                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Dicabut</span>
                        @else
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                            <form method="post" action="{{ route('admin.devices.revoke', $device) }}">
                                @csrf
                                @method('put')
                                <button class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Cabut Akses</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="font-semibold text-slate-950">Belum ada perangkat terhubung</p>
                    <p class="mt-1 text-sm text-slate-500">Perangkat yang berhasil scan QR akan tampil di sini.</p>
                </div>
            @endforelse
        </div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
</x-admin.shell>
