<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashierDevice;
use App\Models\CashierPairingCode;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CashierDeviceController extends Controller
{
    public function index(Request $request): View
    {
        $posInstanceId = $this->posInstanceId($request);

        CashierPairingCode::query()
            ->where('pos_instance_id', $posInstanceId)
            ->whereNull('paired_at')
            ->where('expires_at', '<=', now())
            ->delete();

        return view('admin.devices', [
            'setting' => StoreSetting::query()->where('pos_instance_id', $posInstanceId)->firstOrFail(),
            'pairingBaseUrl' => $this->pairingBaseUrl($request),
            'pairingCodes' => CashierPairingCode::query()
                ->where('pos_instance_id', $posInstanceId)
                ->where(function ($query): void {
                    $query
                        ->where(function ($pending): void {
                            $pending->whereNull('paired_at')
                                ->where('expires_at', '>', now());
                        })
                        ->orWhere(function ($paired): void {
                            $paired->whereNotNull('paired_at')
                                ->whereHas('pairedUser.cashierDevices', function ($devices): void {
                                    $devices->whereNull('revoked_at');
                                });
                        });
                })
                ->latest()
                ->limit(10)
                ->get(),
            'expiredPairingCodeCount' => CashierPairingCode::query()
                ->where('pos_instance_id', $posInstanceId)
                ->whereNull('paired_at')
                ->where('expires_at', '<=', now())
                ->count(),
            'devices' => CashierDevice::query()
                ->with('user')
                ->where('pos_instance_id', $posInstanceId)
                ->whereNull('revoked_at')
                ->latest()
                ->get(),
        ]);
    }

    public function storePairing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cashier_name' => ['required', 'string', 'max:120'],
            'device_label' => ['nullable', 'string', 'max:120'],
        ]);

        do {
            $code = (string) random_int(100000, 999999);
        } while (CashierPairingCode::query()->where('code', $code)->exists());

        CashierPairingCode::query()->create([
            'pos_instance_id' => $this->posInstanceId($request),
            'code' => $code,
            'cashier_name' => $data['cashier_name'],
            'device_label' => $data['device_label'] ?? null,
            'expires_at' => now()->addMinutes(10),
        ]);

        return back()->with('status', "Kode pairing {$code} berhasil dibuat.");
    }

    public function destroyExpiredPairingCodes(Request $request): RedirectResponse
    {
        $deleted = CashierPairingCode::query()
            ->where('pos_instance_id', $this->posInstanceId($request))
            ->whereNull('paired_at')
            ->where('expires_at', '<=', now())
            ->delete();

        return back()->with('status', "{$deleted} QR pairing expired berhasil dihapus.");
    }

    public function destroyPairingCode(Request $request, CashierPairingCode $pairingCode): RedirectResponse
    {
        abort_unless((int) $pairingCode->pos_instance_id === $this->posInstanceId($request), 404);

        if ($pairingCode->paired_at) {
            return back()->withErrors('QR pairing yang sudah terhubung tidak bisa dihapus dari daftar aktif.');
        }

        $pairingCode->delete();

        return back()->with('status', 'QR pairing berhasil dihapus.');
    }

    public function revoke(Request $request, CashierDevice $device): RedirectResponse
    {
        abort_unless((int) $device->pos_instance_id === $this->posInstanceId($request), 404);

        $device->forceFill(['revoked_at' => now()])->save();
        $device->user->tokens()->delete();

        return back()->with('status', 'Akses perangkat kasir berhasil dicabut.');
    }

    private function posInstanceId(Request $request): int
    {
        return (int) $request->attributes->get('arventa_pos_instance')->id;
    }

    private function pairingBaseUrl(Request $request): string
    {
        $host = $request->getHost();
        $scheme = (string) $request->headers->get('x-forwarded-proto', $request->getScheme());
        $scheme = trim(Str::before($scheme, ','));

        if (! in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'https';
        }

        if (app()->environment('production') && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            $scheme = 'https';
        }

        return "{$scheme}://{$host}";
    }
}
