<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\OrderFund;

class OrderPdfController extends Controller
{
    public function disbursementVoucher(OrderFund $order)
    {
        if ($order->created_by !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول لهذا الطلب');
        }

        if ($order->status !== 'EXECUTED') {
            abort(404, 'إذن الصرف متاح فقط للطلبات المنفَّذة');
        }

        $order->load(['items.category', 'executor']);

        return view('client.orders.disbursement-voucher-pdf', compact('order'));
    }

    public function receiptVoucher(OrderFund $order)
    {
        if ($order->created_by !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول لهذا الطلب');
        }
        if ($order->status !== 'EXECUTED') {
            abort(404, 'إيصال القبض متاح فقط للطلبات المنفَّذة');
        }
        if ($order->type !== 'receipt') {
            abort(404, 'هذا الطلب ليس طلب قبض');
        }

        $order->load(['items.category', 'executor']);

        return view('client.orders.receipt-voucher-print', compact('order'));
    }
}
