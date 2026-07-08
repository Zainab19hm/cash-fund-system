<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\OrderFund;

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
