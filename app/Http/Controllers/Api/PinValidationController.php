<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessPin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AccessLog;
use App\Events\AccessLogged;

class PinValidationController extends Controller
{
    public function validatePin(Request $request)
    {
        // 1. Validación de entrada
        $validated = $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]+$/'
        ]);

        // 2. Búsqueda en BD
        $accessPin = AccessPin::where('pin', $validated['pin'])->first();
        $isGranted = $accessPin && $accessPin->is_active;
        $ownerName = $accessPin->owner_name ?? null;

        // 3. Registro de auditoría (opcional pero recomendado)
        Log::info('Access Attempt', [
            'ip' => $request->ip(),
            'pin' => $validated['pin'],
            'status' => $isGranted ? 'granted' : 'denied',
            'owner_name' => $ownerName,
            'is_active' => $accessPin ? $accessPin->is_active : null,
        ]);

        if ($isGranted) {
            $log = AccessLog::create([
                'pin' => $validated['pin'],
                'status' => 'granted',
                'ip_address' => $request->ip(),
                'owner_name' => $ownerName,
            ]);
            AccessLogged::dispatch($log);
            return response()->json(['status' => 'granted', 'user' => $ownerName], 200);
        }

        $log = AccessLog::create([
            'pin' => $validated['pin'],
            'status' => 'denied',
            'ip_address' => $request->ip(),
            'owner_name' => $ownerName,
        ]);
        AccessLogged::dispatch($log);
        return response()->json([
            'status' => 'denied',
            'user' => $ownerName,
            'reason' => $accessPin ? 'inactive' : 'not_found',
        ], 403);
}}