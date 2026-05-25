<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierDevice;
use App\Models\CashierPairingCode;
use App\Models\PosInstance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PairingController extends Controller
{
    public function connect(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'digits:6'],
            'device_name' => ['required', 'string', 'max:120'],
            'device_uid' => ['nullable', 'string', 'max:120'],
        ]);

        $pairing = CashierPairingCode::query()
            ->where('code', $payload['code'])
            ->first();

        if (! $pairing || $pairing->paired_at || $pairing->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['Kode pairing tidak valid, sudah dipakai, atau kedaluwarsa.'],
            ]);
        }

        $result = DB::transaction(function () use ($pairing, $payload): array {
            $posInstanceId = $pairing->pos_instance_id ?: PosInstance::query()->orderBy('id')->value('id');

            if (! $posInstanceId) {
                throw ValidationException::withMessages([
                    'code' => ['POS untuk kode pairing belum tersedia.'],
                ]);
            }

            $existingDevice = ! empty($payload['device_uid'])
                ? CashierDevice::query()
                    ->with('user')
                    ->where('pos_instance_id', $posInstanceId)
                    ->where('device_uid', $payload['device_uid'])
                    ->first()
                : null;

            if ($existingDevice?->user) {
                $existingDevice->user->tokens()->delete();
            }

            $username = 'cashier_'.Str::lower(Str::random(10));
            $user = User::query()->create([
                'name' => $pairing->cashier_name,
                'email' => $username.'@cashier.arventa.local',
                'username' => $username,
                'password' => Str::random(32),
                'role' => 'cashier',
                'is_active' => true,
                'pos_instance_id' => $posInstanceId,
            ]);

            $deviceData = [
                'pos_instance_id' => $posInstanceId,
                'user_id' => $user->id,
                'device_name' => $payload['device_name'],
                'device_uid' => $payload['device_uid'] ?? null,
                'paired_at' => now(),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ];

            if ($existingDevice) {
                $existingDevice->update($deviceData);
                $device = $existingDevice->refresh();
            } else {
                $device = CashierDevice::query()->create($deviceData);
            }

            $pairing->update([
                'paired_at' => now(),
                'paired_user_id' => $user->id,
            ]);

            return [
                'user' => $user,
                'device' => $device,
                'token' => $user->createToken('cashier-device-'.$device->id)->plainTextToken,
            ];
        });

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $result['token'],
            'cashier' => $result['user']->only(['id', 'name', 'username', 'role']),
            'device' => $result['device']->only(['id', 'device_name', 'paired_at']),
        ], 201);
    }
}
