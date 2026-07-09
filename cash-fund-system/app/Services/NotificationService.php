<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\OrderFund;
use App\Models\User;

class NotificationService
{
    public function notify(OrderFund $order, string $type): void
    {
        $messages = [
            'APPROVED' => "تم اعتماد طلبك رقم {$order->order_number}",
            'REJECTED' => "تم رفض طلبك رقم {$order->order_number}" .
                ($order->rejection_reason ? " — السبب: {$order->rejection_reason}" : ''),
            'EXECUTED' => "تم تنفيذ طلبك رقم {$order->order_number}",
        ];

        Notification::create([
            'user_id' => $order->created_by,
            'order_id' => $order->id,
            'type' => $type,
            'message' => $messages[$type],
            'is_read' => false,
        ]);
    }

    public function notifyAdminsNewOrder(OrderFund $order): void
    {
        $creatorName = $order->creator->name ?? 'عميل';
        $typeLabel = $order->type === 'payment' ? 'صرف' : 'قبض';
        $message = "طلب {$typeLabel} جديد من {$creatorName} — رقم الطلب: {$order->order_number} — المبلغ: " . number_format($order->amount, 2);

        $admins = User::where('role', 'admin')->where('is_active', true)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'order_id' => $order->id,
                'type' => 'NEW_ORDER',
                'message' => $message,
                'is_read' => false,
            ]);
        }
    }

    public function markAsRead(Notification $notification, int $userId): void
    {
        if ($notification->user_id !== $userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('غير مصرح');
        }
        $notification->update(['is_read' => true, 'read_at' => now()]);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }
}
