<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DailyMovement;
use App\Models\LogAudit;
use App\Models\OrderFund;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    // RPT-01: يومية الصندوق — كل حركات يوم/فترة معينة
    public function dailyJournal(?string $date = null): Collection
    {
        return DailyMovement::with('order')
            ->when($date, fn ($q) => $q->whereDate('movement_date', $date))
            ->orderBy('executed_at')
            ->get();
    }

    // RPT-02: كشف الأوامر وحالتها
    public function ordersWithStatus(?string $status = null, ?string $from = null, ?string $to = null): LengthAwarePaginator
    {
        return OrderFund::with('creator')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->where('order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('order_date', '<=', $to))
            ->latest('order_date')
            ->paginate(30);
    }

    // RPT-03: تفصيل بنود الأمر
    public function orderItemsDetail(int $orderId): Collection
    {
        return OrderItem::with('category')
            ->where('order_id', $orderId)
            ->get();
    }

    // RPT-04: الوثائق الناقصة — طلبات بدون أي وثيقة
    public function missingDocuments(): Collection
    {
        return OrderFund::whereIn('status', ['PENDING', 'APPROVED', 'EXECUTED'])
            ->whereDoesntHave('documents')
            ->with('creator')
            ->get();
    }

    // RPT-05: نشاط المستخدمين — من log_audit مباشرة
    public function userActivity(?int $userId = null, ?string $from = null, ?string $to = null): LengthAwarePaginator
    {
        return LogAudit::with('user')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(50);
    }

    // RPT-06: الرصيد الحالي — آخر صف بـ daily_movements
    public function currentBalance(): string
    {
        $last = DailyMovement::orderByDesc('id')->first();

        return $last ? $last->balance_after : '0.00';
    }

    // RPT-07: كشف الحركة لفترة
    public function movementStatement(string $from, string $to): LengthAwarePaginator
    {
        return DailyMovement::with('order')
            ->whereBetween('movement_date', [$from, $to])
            ->orderBy('movement_date')
            ->paginate(30);
    }

    // RPT-08: إجمالي الصرف والقبض (لفترة اختيارية)
    public function totalsByType(?string $from = null, ?string $to = null): array
    {
        $query = DailyMovement::query()
            ->when($from, fn ($q) => $q->where('movement_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('movement_date', '<=', $to));

        return [
            'payment' => (clone $query)->where('movement_type', 'payment')->sum('amount'),
            'receipt' => (clone $query)->where('movement_type', 'receipt')->sum('amount'),
        ];
    }

    // RPT-09: الصرف حسب البند — عبر order_items بالطلبات EXECUTED فقط
    public function expensesByCategory(?string $from = null, ?string $to = null): Collection
    {
        return OrderItem::query()
            ->join('orders_fund', 'orders_fund.id', '=', 'order_items.order_id')
            ->join('categories', 'categories.id', '=', 'order_items.category_id')
            ->where('orders_fund.status', 'EXECUTED')
            ->when($from, fn ($q) => $q->where('orders_fund.order_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('orders_fund.order_date', '<=', $to))
            ->select('categories.name', \DB::raw('SUM(order_items.amount) as total'))
            ->groupBy('categories.name')
            ->get();
    }

    // RPT-10: تقرير الوثائق (حالة الأرشفة لكل طلب)
    public function documentsArchiveStatus(): LengthAwarePaginator
    {
        return OrderFund::withCount('documents')
            ->with('creator')
            ->orderByDesc('order_date')
            ->paginate(30);
    }

    // RPT-11: تقرير الصلاحيات (إنشاء/موافقة لكل مستخدم)
    public function permissionsReport(): Collection
    {
        return User::withCount([
            'createdOrders as created_count',
            'approvedOrders as approved_count',
        ])->get();
    }

    // RPT-12: تقرير الأوامر المعلقة (للمستثمر تحديداً)
    public function pendingOrdersReport(): Collection
    {
        return OrderFund::where('status', 'PENDING')
            ->with('creator')
            ->orderBy('created_at')
            ->get();
    }

    // RPT-13: داشبورد الطلبات (منفَّذ/معلق/مرفوض) — عدادات فقط
    public function ordersDashboard(): array
    {
        return [
            'executed'  => OrderFund::where('status', 'EXECUTED')->count(),
            'pending'   => OrderFund::where('status', 'PENDING')->count(),
            'rejected'  => OrderFund::where('status', 'REJECTED')->count(),
            'draft'     => OrderFund::where('status', 'DRAFT')->count(),
            'approved'  => OrderFund::where('status', 'APPROVED')->count(),
            'cancelled' => OrderFund::where('status', 'CANCELLED')->count(),
        ];
    }

    // RPT-14: سجل التدقيق (الطالب، الموافق، التاريخ) — مبني فوق orders_fund
    public function auditTrailReport(): LengthAwarePaginator
    {
        return OrderFund::whereNotNull('approved_by')
            ->orWhereNotNull('rejected_by')
            ->with(['creator', 'approver', 'rejector', 'executor'])
            ->latest()
            ->paginate(30);
    }
}
