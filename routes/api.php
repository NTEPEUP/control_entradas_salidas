<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\PinValidationController;
use App\Http\Controllers\DashboardController;
use App\Models\AccessLog;
use App\Models\AccessPin;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/validate-pin', [PinValidationController::class, 'validatePin'])
     ->middleware('throttle:10,1'); // Máx 10 intentos por minuto por IP

Route::get('/access-logs', function () {
    $recent = AccessLog::latest()->take(10)->get();
    $total = AccessLog::count();
    $granted = AccessLog::where('status', 'granted')->count();
    $denied = AccessLog::where('status', 'denied')->count();

    return response()->json([
        'recent' => $recent,
        'total' => $total,
        'granted' => $granted,
        'denied' => $denied,
        'stats' => [
            'total' => $total,
            'granted' => $granted,
            'denied' => $denied,
            'rate' => $total > 0 ? round(($granted / $total) * 100, 1) : 0,
        ],
    ]);
});