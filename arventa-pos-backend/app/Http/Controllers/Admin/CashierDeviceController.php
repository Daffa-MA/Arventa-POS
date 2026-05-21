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
    public function index(): View
    {
        CashierPairingCode::query()
            ->whereNull('paired_at')
            ->where('expires_at', '<=', now())
            ->delete();

        return view('admin.devices', [
            'setting' => StoreSetting::query()->firstOrFail(),
            'pairingCodes' => CashierPairingCode::query()
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
                ->whereNull('paired_at')
                ->where('expires_at', '<=', now())
                ->count(),
            'devices' => CashierDevice::query()
                ->with('user')
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
            'code' => $code,
            'cashier_name' => $data['cashier_name'],
            'device_label' => $data['device_label'] ?? null,
            'expires_at' => now()->addMinutes(10),
        ]);

        return back()->with('status', "Kode pairing {$code} berhasil dibuat.");
    }

    public function destroyExpiredPairingCodes(): RedirectResponse
    {
        $deleted = CashierPairingCode::query()
            ->whereNull('paired_at')
            ->where('expires_at', '<=', now())
            ->delete();

        return back()->with('status', "{$deleted} QR pairing expired berhasil dihapus.");
    }

    public function destroyPairingCode(CashierPairingCode $pairingCode): RedirectResponse
    {
        if ($pairingCode->paired_at) {
            return back()->withErrors('QR pairing yang sudah terhubung tidak bisa dihapus dari daftar aktif.');
        }

        $pairingCode->delete();

        return back()->with('status', 'QR pairing berhasil dihapus.');
    }

    public function revoke(CashierDevice $device): RedirectResponse
    {
        $device->forceFill(['revoked_at' => now()])->save();
        $device->user->tokens()->delete();

        return back()->with('status', 'Akses perangkat kasir berhasil dicabut.');
    }
}
