<?php

use App\Http\Controllers\AccessPinController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/access-pins', [AccessPinController::class, 'index'])->name('access-pins.index');
Route::post('/access-pins', [AccessPinController::class, 'store'])->name('access-pins.store');
Route::patch('/access-pins/{accessPin}/toggle', [AccessPinController::class, 'toggle'])->name('access-pins.toggle');
