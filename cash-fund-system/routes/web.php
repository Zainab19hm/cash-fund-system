<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Investor\ReportController as InvestorReportController;
use App\Http\Controllers\Client\OrderController;
use App\Http\Controllers\Client\OrderPdfController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// ── Auth Routes ──────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])
    ->name('login')
    ->middleware('throttle:login');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ── Admin Routes ─────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('admin.reports.dashboard'))->name('admin.dashboard');

    // Users CRUD
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/suggest-password', [UserController::class, 'suggestPassword'])->name('suggest-password');
    });

    // Permissions
    Route::prefix('permissions')->name('admin.permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::post('/update', [PermissionController::class, 'update'])->name('update');
    });

    // Categories
    Route::prefix('categories')->name('admin.categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::post('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Orders (Admin)
    Route::prefix('orders')->name('admin.orders.')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->name('index');
        Route::get('/{order}', [AdminOrderController::class, 'show'])->name('show');
        Route::get('/{order}/report', [AdminOrderController::class, 'report'])->name('report');
        Route::get('/{order}/documents/{document}/download', [AdminOrderController::class, 'downloadDocument'])->name('download-document');
        Route::post('/{order}/approve', [AdminOrderController::class, 'approve'])->name('approve');
        Route::post('/{order}/reject', [AdminOrderController::class, 'reject'])->name('reject');
        Route::post('/{order}/execute', [AdminOrderController::class, 'execute'])->name('execute');
        Route::post('/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('cancel');
    });

    // Reports (Admin — all 14 reports)
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        Route::get('/dashboard', [AdminReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/daily-journal', [AdminReportController::class, 'dailyJournal'])->name('daily-journal');
        Route::get('/orders-status', [AdminReportController::class, 'ordersStatus'])->name('orders-status');
        Route::get('/order-items/{order}', [AdminReportController::class, 'orderItems'])->name('order-items');
        Route::get('/missing-documents', [AdminReportController::class, 'missingDocuments'])->name('missing-documents');
        Route::get('/user-activity', [AdminReportController::class, 'userActivity'])->name('user-activity');
        Route::get('/current-balance', [AdminReportController::class, 'currentBalance'])->name('current-balance');
        Route::get('/movement-statement', [AdminReportController::class, 'movementStatement'])->name('movement-statement');
        Route::get('/totals', [AdminReportController::class, 'totals'])->name('totals');
        Route::get('/expenses-by-category', [AdminReportController::class, 'expensesByCategory'])->name('expenses-by-category');
        Route::get('/documents-archive', [AdminReportController::class, 'documentsArchive'])->name('documents-archive');
        Route::get('/permissions-report', [AdminReportController::class, 'permissionsReport'])->name('permissions-report');
        Route::get('/audit-trail', [AdminReportController::class, 'auditTrail'])->name('audit-trail');

        // Print routes
        Route::get('/daily-journal/print', [AdminReportController::class, 'dailyJournalPrint'])->name('daily-journal.print');
        Route::get('/orders-status/print', [AdminReportController::class, 'ordersStatusPrint'])->name('orders-status.print');
        Route::get('/missing-documents/print', [AdminReportController::class, 'missingDocumentsPrint'])->name('missing-documents.print');
        Route::get('/user-activity/print', [AdminReportController::class, 'userActivityPrint'])->name('user-activity.print');
        Route::get('/current-balance/print', [AdminReportController::class, 'currentBalancePrint'])->name('current-balance.print');
        Route::get('/movement-statement/print', [AdminReportController::class, 'movementStatementPrint'])->name('movement-statement.print');
        Route::get('/totals/print', [AdminReportController::class, 'totalsPrint'])->name('totals.print');
        Route::get('/expenses-by-category/print', [AdminReportController::class, 'expensesByCategoryPrint'])->name('expenses-by-category.print');
        Route::get('/documents-archive/print', [AdminReportController::class, 'documentsArchivePrint'])->name('documents-archive.print');
        Route::get('/permissions-report/print', [AdminReportController::class, 'permissionsReportPrint'])->name('permissions-report.print');
        Route::get('/audit-trail/print', [AdminReportController::class, 'auditTrailPrint'])->name('audit-trail.print');
    });
});

// ── Investor Routes ──────────────────────────────────────────────
Route::middleware(['auth', 'role:investor'])->prefix('investor')->name('investor.')->group(function () {
    Route::get('/dashboard', [InvestorReportController::class, 'dashboard'])->name('dashboard');
    Route::get('/current-balance', [InvestorReportController::class, 'currentBalance'])->name('current-balance');
    Route::get('/movement-statement', [InvestorReportController::class, 'movementStatement'])->name('movement-statement');
    Route::get('/totals', [InvestorReportController::class, 'totals'])->name('totals');
    Route::get('/expenses-by-category', [InvestorReportController::class, 'expensesByCategory'])->name('expenses-by-category');
    Route::get('/pending-orders', [InvestorReportController::class, 'pendingOrders'])->name('pending-orders');

    // Print routes
    Route::get('/current-balance/print', [InvestorReportController::class, 'currentBalancePrint'])->name('current-balance.print');
    Route::get('/movement-statement/print', [InvestorReportController::class, 'movementStatementPrint'])->name('movement-statement.print');
    Route::get('/totals/print', [InvestorReportController::class, 'totalsPrint'])->name('totals.print');
    Route::get('/expenses-by-category/print', [InvestorReportController::class, 'expensesByCategoryPrint'])->name('expenses-by-category.print');
    Route::get('/pending-orders/print', [InvestorReportController::class, 'pendingOrdersPrint'])->name('pending-orders.print');
});

// ── Client Routes ────────────────────────────────────────────────
Route::middleware(['auth', 'role:client'])->prefix('client')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('client.orders.index'))->name('client.dashboard');

    Route::prefix('orders')->name('client.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/submit', [OrderController::class, 'submit'])->name('submit');
        Route::post('/{order}/upload-document', [OrderController::class, 'uploadDocument'])->name('upload-document');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::get('/{order}/disbursement-voucher', [OrderPdfController::class, 'disbursementVoucher'])->name('disbursement-voucher');
        Route::get('/{order}/receipt-voucher', [OrderPdfController::class, 'receiptVoucher'])->name('receipt-voucher');
    });

    Route::get('/account-statement', [OrderController::class, 'accountStatement'])->name('client.orders.account-statement');
});

// ── Notification Routes (shared across roles) ──────────────────
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
});
