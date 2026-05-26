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
                    <p class="mt-1 text-sm text-slate-500">Scan QR dari app kasir. QR sudah membawa URL tenant dan kode pairing.</p>
                </div>
                @if ($expiredPairingCodeCount > 0)
                    <form method="post" action="{{ route('admin.devices.pairing-codes.expired.destroy') }}">
                        @csrf
                        @method('delete')
                        <button class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 active:scale-[0.97]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-.8 14.2A2 2 0 0 1 16.2 22H7.8a2 2 0 0 1-2-1.8L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                            Hapus expired
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @forelse ($pairingCodes as $pairing)
                    @php
                        $payload = json_encode([
                            'type' => 'arventa_pairing',
                            'code' => $pairing->code,
                            'base_url' => $pairingBaseUrl,
                            'api_url' => "{$pairingBaseUrl}/api/pairing/connect",
                        ], JSON_UNESCAPED_SLASHES);
                        $isPaired = (bool) $pairing->paired_at;
                    @endphp
                    <div
                        x-data="{
                            qrPayload: @js($payload),
                            qrId: 'qr-{{ $pairing->id }}',
                            isPaired: @js($isPaired),
                            expiresAt: new Date(@js($pairing->expires_at->toIso8601String())).getTime(),
                            remaining: 0,
                            visible: true,
                            timer: null,
                            init() {
                                this.$nextTick(() => window.QRCode && new QRCode(document.getElementById(this.qrId), { text: this.qrPayload, width: 148, height: 148 }))
                                this.tick()
                                this.timer = setInterval(() => this.tick(), 1000)
                            },
                            tick() {
                                if (this.isPaired) {
                                    this.remaining = 0
                                    return
                                }
                                this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000))
                                if (this.remaining <= 0 && this.visible) {
                                    this.visible = false
                                    clearInterval(this.timer)
                                    this.deleteExpired()
                                }
                            },
                            remainingText() {
                                const minutes = Math.floor(this.remaining / 60).toString().padStart(2, '0')
                                const seconds = (this.remaining % 60).toString().padStart(2, '0')
                                return `${minutes}:${seconds}`
                            },
                            deleteExpired() {
                                fetch(@js(route('admin.devices.pairing-codes.destroy', $pairing)), {
                                    method: 'DELETE',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': @js(csrf_token()),
                                    },
                                }).catch(() => {})
                            }
                        }"
                        x-show="visible"
                        x-transition.opacity.scale.95
                        class="rounded-2xl border {{ $isPaired ? 'border-emerald-100 bg-emerald-50/30' : 'border-blue-100 bg-white' }} p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $pairing->cashier_name }}</p>
                                <p class="text-xs text-slate-500">{{ $pairing->device_label ?: 'Tanpa label perangkat' }}</p>
                            </div>
                            @if ($isPaired)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Terhubung</span>
                            @else
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Aktif</span>
                            @endif
                        </div>
                        <div class="mt-4 flex items-center gap-4">
                            <div id="qr-{{ $pairing->id }}" class="flex h-[148px] w-[148px] items-center justify-center rounded-xl bg-white p-2 ring-1 ring-slate-200"></div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</p>
                                <p class="mt-1 font-mono text-3xl font-bold tracking-widest text-slate-950">{{ $pairing->code }}</p>
                                @if ($isPaired)
                                    <p class="mt-2 text-xs font-semibold text-emerald-700">Sudah terhubung</p>
                                    <p class="mt-1 text-[11px] text-slate-400">Pairing {{ $pairing->paired_at->format('H:i') }}</p>
                                @else
                                    <p class="mt-2 text-xs text-slate-500">Sisa waktu <span class="font-semibold text-slate-700" x-text="remainingText()">--:--</span></p>
                                    <p class="mt-1 text-[11px] text-slate-400">QR ini akan berakhir dalam 10 menit.</p>
                                    <form method="post" action="{{ route('admin.devices.pairing-codes.destroy', $pairing) }}" class="mt-3">
                                        @csrf
                                        @method('delete')
                                        <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 active:scale-[0.97]">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-.8 14.2A2 2 0 0 1 16.2 22H7.8a2 2 0 0 1-2-1.8L5 6"/></svg>
                                            Batalkan QR
                                        </button>
                                    </form>
                                @endif
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
