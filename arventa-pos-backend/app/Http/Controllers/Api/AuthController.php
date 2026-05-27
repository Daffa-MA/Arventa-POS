<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierDevice;
use App\Models\CashierPairingCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('role', 'cashier')
            ->where('is_active', true)
            ->whereNotNull('pos_instance_id')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'Android Cashier')->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'pos_instance_id']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $tokenName = '';

        if (is_object($token)) {
            $tokenName = method_exists($token, 'getAttribute')
                ? (string) $token->getAttribute('name')
                : (string) ($token->name ?? '');
        }

        DB::transaction(function () use ($request, $user, $token, $tokenName): void {
            $device = $this->currentCashierDevice($request, $user, $tokenName);

            if ($device) {
                CashierPairingCode::query()
                    ->where('pos_instance_id', $device->pos_instance_id)
                    ->where('paired_user_id', $user?->id)
                    ->delete();

                $device->delete();
            }

            if ($user) {
                $user->tokens()->delete();
            } elseif (is_object($token) && method_exists($token, 'delete')) {
                $token->delete();
            }
        });

        return response()->json([
            'message' => 'Perangkat kasir berhasil logout.',
        ]);
    }

    private function currentCashierDevice(Request $request, ?User $user, string $tokenName): ?CashierDevice
    {
        if (! $user) {
            return null;
        }

        if (preg_match('/^cashier-device-(\d+)$/', $tokenName, $matches)) {
            $device = CashierDevice::query()
                ->whereKey((int) $matches[1])
                ->where('user_id', $user->id)
                ->first();

            if ($device) {
                return $device;
            }
        }

        $deviceUid = trim((string) $request->input('device_uid', ''));

        if ($deviceUid !== '') {
            $device = CashierDevice::query()
                ->where('user_id', $user->id)
                ->where('device_uid', $deviceUid)
                ->latest()
                ->first();

            if ($device) {
                return $device;
            }
        }

        return CashierDevice::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();
    }
}
