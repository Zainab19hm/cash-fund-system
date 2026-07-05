<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\OrderFund;
use App\Models\Category;
use App\Services\OrderService;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected DocumentService $documentService,
    ) {}

    public function index()
    {
        $orders = OrderFund::where('created_by', auth()->id())
            ->latest()
            ->paginate(15);

        return view('client.orders.index', compact('orders'));
    }

    public function create()
    {
        $categories = Category::active()->get();

        return view('client.orders.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => 'required|in:payment,receipt',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'order_date'  => 'required|date',
            'notes'       => 'nullable|string|max:1000',
            'items'       => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount'      => 'required|numeric|min:0.01',
        ]);

        $order = $this->orderService->createDraft(
            $validated,
            $validated['items'],
            auth()->id()
        );

        return redirect()->route('client.orders.show', $order)
            ->with('success', 'تم إنشاء الطلب بنجاح');
    }

    public function show(OrderFund $order)
    {
        if ($order->created_by !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول لهذا الطلب');
        }

        $order->load(['items.category', 'documents']);

        return view('client.orders.show', compact('order'));
    }

    public function submit(OrderFund $order)
    {
        if ($order->created_by !== auth()->id()) {
            abort(403, 'غير مصرح لك بهذه العملية');
        }

        $this->orderService->submitForApproval($order);

        return redirect()->route('client.orders.show', $order)
            ->with('success', 'تم إرسال الطلب للاعتماد بنجاح');
    }

    public function uploadDocument(Request $request, OrderFund $order)
    {
        if ($order->created_by !== auth()->id()) {
            abort(403, 'غير مصرح لك بهذه العملية');
        }

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $this->documentService->upload($order, $request->file('file'), auth()->id());

        return redirect()->route('client.orders.show', $order)
            ->with('success', 'تم رفع الوثيقة بنجاح');
    }
}
