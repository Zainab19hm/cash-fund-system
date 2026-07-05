<?php

namespace App\Services;

use App\Models\OrderFund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    protected OrderNumberService $orderNumberService;

    public function __construct(OrderNumberService $orderNumberService)
    {
        $this->orderNumberService = $orderNumberService;
    }

    /**
     * Create a draft order with items.
     * Verifies that sum(items.amount) === $data['amount'] using bcadd/bccomp
     * for precise DECIMAL comparison (avoids float issues).
     */
    public function createDraft(array $data, array $items, int $clientId): OrderFund
    {
        $itemsTotal = collect($items)->reduce(function ($sum, $item) {
            return bcadd($sum, number_format($item['amount'], 2, '.', ''), 2);
        }, '0.00');

        if (bccomp($itemsTotal, number_format($data['amount'], 2, '.', ''), 2) !== 0) {
            throw ValidationException::withMessages([
                'amount' => 'مجموع بنود الطلب (' . $itemsTotal . ') لا يطابق الإجمالي (' . $data['amount'] . ')',
            ]);
        }

        return DB::transaction(function () use ($data, $items, $clientId) {
            $order = OrderFund::create([
                'order_number' => $this->orderNumberService->generate(),
                'type'         => $data['type'],
                'amount'       => $data['amount'],
                'description'  => $data['description'] ?? null,
                'status'       => 'DRAFT',
                'order_date'   => $data['order_date'],
                'created_by'   => $clientId,
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'category_id' => $item['category_id'],
                    'description' => $item['description'],
                    'amount'      => $item['amount'],
                ]);
            }

            return $order;
        });
    }

    /**
     * Submit an order for approval.
     * Changes status from DRAFT to PENDING.
     * Verifies: current status is DRAFT, item count >= 1.
     */
    public function submitForApproval(OrderFund $order): void
    {
        if ($order->status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن إرسال طلب بحالة ' . $order->status . ' — يجب أن يكون مسودة',
            ]);
        }

        if ($order->items()->count() < 1) {
            throw ValidationException::withMessages([
                'items' => 'لا يمكن إرسال طلب بدون بنود',
            ]);
        }

        $order->update(['status' => 'PENDING']);

        // TODO: notification (Phase 5)
    }
}
