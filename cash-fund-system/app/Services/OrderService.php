<?php

namespace App\Services;

use App\Models\DailyMovement;
use App\Models\OrderFund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    protected OrderNumberService $orderNumberService;
    protected NotificationService $notificationService;

    public function __construct(OrderNumberService $orderNumberService, NotificationService $notificationService)
    {
        $this->orderNumberService = $orderNumberService;
        $this->notificationService = $notificationService;
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
    }

    public function approve(OrderFund $order, int $approvedBy): void
    {
        if ($order->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن اعتماد طلب ليس بحالة قيد الانتظار',
            ]);
        }

        if ($order->created_by === $approvedBy) {
            throw ValidationException::withMessages([
                'approved_by' => 'لا يمكن لمنشئ الطلب اعتماده بنفسه',
            ]);
        }

        DB::transaction(function () use ($order, $approvedBy) {
            $order->update([
                'status'      => 'APPROVED',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);
            $this->notificationService->notify($order, 'APPROVED');
        });
    }

    public function reject(OrderFund $order, int $rejectedBy, string $reason): void
    {
        if ($order->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن رفض طلب ليس بحالة قيد الانتظار',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'سبب الرفض إلزامي',
            ]);
        }

        DB::transaction(function () use ($order, $rejectedBy, $reason) {
            $order->update([
                'status'           => 'REJECTED',
                'rejected_by'      => $rejectedBy,
                'rejection_reason' => $reason,
            ]);
            $this->notificationService->notify($order, 'REJECTED');
        });
    }

    public function cancel(OrderFund $order, int $cancelledBy): void
    {
        if (! in_array($order->status, ['DRAFT', 'PENDING'])) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن إلغاء طلب بهذه الحالة',
            ]);
        }

        $order->update([
            'status'       => 'CANCELLED',
            'cancelled_by' => $cancelledBy,
        ]);
    }

    public function execute(OrderFund $order, int $executedBy): void
    {
        if ($order->status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن تنفيذ طلب لم يُعتمد بعد',
            ]);
        }

        DB::transaction(function () use ($order, $executedBy) {
            $lastMovement = DailyMovement::orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $currentBalance = $lastMovement ? $lastMovement->balance_after : '0.00';

            $delta = $order->type === 'receipt'
                ? $order->amount
                : bcmul($order->amount, '-1', 2);

            $newBalance = bcadd($currentBalance, $delta, 2);

            DailyMovement::create([
                'order_id'      => $order->id,
                'movement_type' => $order->type,
                'amount'        => $order->amount,
                'balance_after' => $newBalance,
                'movement_date' => now()->toDateString(),
                'executed_at'   => now(),
            ]);

            $order->update([
                'status'      => 'EXECUTED',
                'executed_by' => $executedBy,
                'executed_at' => now(),
            ]);

            $this->notificationService->notify($order, 'EXECUTED');
        });
    }
}
