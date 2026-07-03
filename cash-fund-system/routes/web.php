<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// ── Auth Routes ──────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])
    ->name('login');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ── Dashboard Placeholders ───────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', fn() => 'Admin Dashboard - OK')->name('admin.dashboard');
});

Route::middleware(['auth', 'role:investor'])->prefix('investor')->group(function () {
    Route::get('/dashboard', fn() => 'Investor Dashboard - OK')->name('investor.dashboard');
});

Route::middleware(['auth', 'role:client'])->prefix('client')->group(function () {
    Route::get('/dashboard', fn() => 'Client Dashboard - OK')->name('client.dashboard');
});
