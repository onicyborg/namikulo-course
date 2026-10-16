<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTimezoneController extends Controller
{
    public function update(Request $request, ActivityLogger $logger): JsonResponse
    {
        $data = $request->validate(['timezone' => ['required', 'timezone']]);
        DB::transaction(function () use ($request, $data, $logger): void {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($user->timezone === $data['timezone']) return;

            $old = ['timezone' => $user->timezone];
            $user->update(['timezone' => $data['timezone']]);
            $logger->log('updated', $user, 'users', $user->id, $old, ['timezone' => $user->timezone], 'Zona waktu perangkat diperbarui');
        });

        return response()->json(['timezone' => $data['timezone']]);
    }
}
