<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderFund;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    // RPT-13: داشبورد الطلبات
    public function dashboard()
    {
        return view('admin.reports.dashboard', [
            'counts' => $this->reportService->ordersDashboard(),
        ]);
    }

    // RPT-01: يومية الصندوق
    public function dailyJournal(Request $request)
    {
        $date = $request->input('date');

        return view('admin.reports.daily-journal', [
            'movements' => $this->reportService->dailyJournal($date),
            'date' => $date,
        ]);
    }

    // RPT-02: كشف الأوامر وحالتها
    public function ordersStatus(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:DRAFT,PENDING,APPROVED,REJECTED,EXECUTED,CANCELLED',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return view('admin.reports.orders-status', [
            'orders' => $this->reportService->ordersWithStatus(
                $request->input('status'),
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['status', 'from', 'to']),
        ]);
    }

    // RPT-03: تفصيل بنود الأمر
    public function orderItems(OrderFund $order)
    {
        return view('admin.reports.order-items', [
            'order' => $order,
            'items' => $this->reportService->orderItemsDetail($order->id),
        ]);
    }

    // RPT-04: الوثائق الناقصة
    public function missingDocuments()
    {
        return view('admin.reports.missing-documents', [
            'orders' => $this->reportService->missingDocuments(),
        ]);
    }

    // RPT-05: نشاط المستخدمين
    public function userActivity(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return view('admin.reports.user-activity', [
            'activities' => $this->reportService->userActivity(
                $request->input('user_id'),
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['user_id', 'from', 'to']),
        ]);
    }

    // RPT-06: الرصيد الحالي
    public function currentBalance()
    {
        return view('admin.reports.current-balance', [
            'balance' => $this->reportService->currentBalance(),
        ]);
    }

    // RPT-07: كشف الحركة لفترة
    public function movementStatement(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $movements = null;
        if ($from && $to) {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
            ]);

            $movements = $this->reportService->movementStatement($from, $to);
        }

        return view('admin.reports.movement-statement', [
            'movements' => $movements,
            'from' => $from,
            'to' => $to,
        ]);
    }

    // RPT-08: إجمالي الصرف والقبض
    public function totals(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return view('admin.reports.totals', [
            'totals' => $this->reportService->totalsByType(
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    // RPT-09: الصرف حسب البند
    public function expensesByCategory(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return view('admin.reports.expenses-by-category', [
            'expenses' => $this->reportService->expensesByCategory(
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    // RPT-10: تقرير الوثائق
    public function documentsArchive()
    {
        return view('admin.reports.documents-archive', [
            'orders' => $this->reportService->documentsArchiveStatus(),
        ]);
    }

    // RPT-11: تقرير الصلاحيات
    public function permissionsReport()
    {
        return view('admin.reports.permissions-report', [
            'users' => $this->reportService->permissionsReport(),
        ]);
    }

    // RPT-14: سجل التدقيق
    public function auditTrail()
    {
        return view('admin.reports.audit-trail', [
            'orders' => $this->reportService->auditTrailReport(),
        ]);
    }

    // ── Print Methods ─────────────────────────────────────────────

    public function dailyJournalPrint(Request $request)
    {
        $date = $request->input('date');
        return view('admin.reports.prints.daily-journal', [
            'movements' => $this->reportService->dailyJournal($date),
            'date' => $date,
        ]);
    }

    public function ordersStatusPrint(Request $request)
    {
        return view('admin.reports.prints.orders-status', [
            'orders' => $this->reportService->ordersWithStatus(
                $request->input('status'),
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['status', 'from', 'to']),
        ]);
    }

    public function missingDocumentsPrint()
    {
        return view('admin.reports.prints.missing-documents', [
            'orders' => $this->reportService->missingDocuments(),
        ]);
    }

    public function userActivityPrint(Request $request)
    {
        return view('admin.reports.prints.user-activity', [
            'activities' => $this->reportService->userActivity(
                $request->input('user_id'),
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['user_id', 'from', 'to']),
        ]);
    }

    public function currentBalancePrint()
    {
        return view('admin.reports.prints.current-balance', [
            'balance' => $this->reportService->currentBalance(),
        ]);
    }

    public function movementStatementPrint(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $movements = null;
        if ($from && $to) {
            $movements = $this->reportService->movementStatement($from, $to);
        }
        return view('admin.reports.prints.movement-statement', [
            'movements' => $movements,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function totalsPrint(Request $request)
    {
        return view('admin.reports.prints.totals', [
            'totals' => $this->reportService->totalsByType(
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function expensesByCategoryPrint(Request $request)
    {
        return view('admin.reports.prints.expenses-by-category', [
            'expenses' => $this->reportService->expensesByCategory(
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function documentsArchivePrint()
    {
        return view('admin.reports.prints.documents-archive', [
            'orders' => $this->reportService->documentsArchiveStatus(),
        ]);
    }

    public function permissionsReportPrint()
    {
        return view('admin.reports.prints.permissions-report', [
            'users' => $this->reportService->permissionsReport(),
        ]);
    }

    public function auditTrailPrint()
    {
        return view('admin.reports.prints.audit-trail', [
            'orders' => $this->reportService->auditTrailReport(),
        ]);
    }
}
