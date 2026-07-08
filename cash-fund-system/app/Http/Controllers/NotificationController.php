<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        $this->notificationService->markAsRead($notification, auth()->id());

        return back();
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => $this->notificationService->unreadCount(auth()->id()),
        ]);
    }
}
