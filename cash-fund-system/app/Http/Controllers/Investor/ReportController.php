<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    // لوحة المستثمر — ت شاملة
    public function dashboard()
    {
        return view('investor.dashboard', [
            'balance' => $this->reportService->currentBalance(),
            'counts' => $this->reportService->ordersDashboard(),
        ]);
    }

    // RPT-06: الرصيد الحالي
    public function currentBalance()
    {
        return view('investor.reports.current-balance', [
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

        return view('investor.reports.movement-statement', [
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

        return view('investor.reports.totals', [
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

        return view('investor.reports.expenses-by-category', [
            'expenses' => $this->reportService->expensesByCategory(
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    // RPT-12: تقرير الأوامر المعلقة
    public function pendingOrders()
    {
        return view('investor.reports.pending-orders', [
            'orders' => $this->reportService->pendingOrdersReport(),
        ]);
    }

    // ── Print Methods ─────────────────────────────────────────────

    public function currentBalancePrint()
    {
        return view('investor.reports.prints.current-balance', [
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
        return view('investor.reports.prints.movement-statement', [
            'movements' => $movements,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function totalsPrint(Request $request)
    {
        return view('investor.reports.prints.totals', [
            'totals' => $this->reportService->totalsByType(
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function expensesByCategoryPrint(Request $request)
    {
        return view('investor.reports.prints.expenses-by-category', [
            'expenses' => $this->reportService->expensesByCategory(
                $request->input('from'),
                $request->input('to'),
            ),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function pendingOrdersPrint()
    {
        return view('investor.reports.prints.pending-orders', [
            'orders' => $this->reportService->pendingOrdersReport(),
        ]);
    }
}
