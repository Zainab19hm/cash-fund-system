<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\OrderFund;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    public function index(Request $request)
    {
        $query = OrderFund::where('status', '!=', 'DRAFT');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(OrderFund $order)
    {
        $order->load(['items.category', 'documents', 'creator', 'approver', 'executor']);

        return view('admin.orders.show', compact('order'));
    }

    public function report(OrderFund $order)
    {
        $order->load(['items.category', 'documents', 'creator', 'approver', 'executor', 'canceller', 'rejector']);

        return view('admin.orders.report-print', compact('order'));
    }

    public function downloadDocument(OrderFund $order, Document $document)
    {
        if ($document->order_id !== $order->id) {
            abort(404);
        }

        $path = $document->file_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $document->file_name);
    }

    public function approve(OrderFund $order)
    {
        $this->orderService->approve($order, auth()->id());

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'تم اعتماد الطلب بنجاح');
    }

    public function reject(Request $request, OrderFund $order)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $this->orderService->reject($order, auth()->id(), $validated['rejection_reason']);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'تم رفض الطلب');
    }

    public function execute(Request $request, OrderFund $order)
    {
        $request->validate([
            'confirm_execute' => 'required|in:EXECUTE',
        ]);

        $this->orderService->execute($order, auth()->id());

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'تم تنفيذ الطلب بنجاح');
    }

    public function cancel(OrderFund $order)
    {
        $this->orderService->cancel($order, auth()->id());

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'تم إلغاء الطلب');
    }
}
