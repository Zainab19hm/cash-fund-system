<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Client\OrderController;

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

    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::prefix('permissions')->name('admin.permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::post('/update', [PermissionController::class, 'update'])->name('update');
    });

    Route::prefix('categories')->name('admin.categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::post('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');
    });
});

Route::middleware(['auth', 'role:investor'])->prefix('investor')->group(function () {
    Route::get('/dashboard', fn() => 'Investor Dashboard - OK')->name('investor.dashboard');
});

Route::middleware(['auth', 'role:client'])->prefix('client')->group(function () {
    Route::get('/dashboard', fn() => 'Client Dashboard - OK')->name('client.dashboard');

    Route::prefix('orders')->name('client.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/submit', [OrderController::class, 'submit'])->name('submit');
        Route::post('/{order}/upload-document', [OrderController::class, 'uploadDocument'])->name('upload-document');
    });
});
