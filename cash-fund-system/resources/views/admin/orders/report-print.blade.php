<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>تقرير الطلب — {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'IBM Plex Sans Arabic', 'Tajawal', 'Segoe UI', Tahoma, sans-serif;
            font-size: 14px;
            color: #1a1a1a;
            line-height: 1.6;
            direction: rtl;
            background: #fff;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .page { margin: 0; padding: 15mm; box-shadow: none; }
        }

        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        .page {
            max-width: 210mm;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .print-bar {
            text-align: center;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .print-bar button {
            padding: 10px 30px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: #2563eb;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .print-bar button:hover { background: #1d4ed8; }

        .header {
            text-align: center;
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .header .sub { font-size: 14px; color: #666; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .info-box .label { font-size: 12px; color: #6b7280; margin-bottom: 2px; }
        .info-box .value { font-size: 14px; font-weight: 600; }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin: 20px 0 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }

        .items-table td {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            font-size: 13px;
        }

        .items-table .total-row td {
            background-color: #f9fafb;
            font-weight: 700;
            font-size: 14px;
        }

        .items-table .amount-col {
            text-align: left;
            direction: ltr;
        }

        .notes-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 12px;
            background-color: #f9fafb;
        }

        .notes-box .label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
        .notes-box .content { font-size: 14px; }

        .rejection-box {
            border: 1px solid #fca5a5;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 12px;
            background-color: #fef2f2;
        }

        .rejection-box .label { font-size: 12px; color: #dc2626; margin-bottom: 4px; font-weight: 600; }
        .rejection-box .content { font-size: 14px; color: #dc2626; }

        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 50px;
            gap: 30px;
        }

        .signature-box {
            flex: 1;
            text-align: center;
            border-top: 1px solid #9ca3af;
            padding-top: 10px;
        }

        .signature-box .sig-label { font-size: 12px; color: #6b7280; margin-bottom: 5px; }
        .signature-box .sig-name { font-size: 14px; font-weight: 600; }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="no-print print-bar">
        <button onclick="window.print()">طباعة التقرير</button>
    </div>

    <div class="page">
        <div class="header">
            <h1>تقرير الطلب</h1>
            <div class="sub">نظام إدارة الصندوق النقدي</div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <div class="label">رقم الطلب</div>
                <div class="value">{{ $order->order_number }}</div>
            </div>
            <div class="info-box">
                <div class="label">التاريخ</div>
                <div class="value">{{ $order->order_date->format('Y-m-d') }}</div>
            </div>
            <div class="info-box">
                <div class="label">النوع</div>
                <div class="value">{{ $order->type === 'payment' ? 'صرف' : 'قبض' }}</div>
            </div>
            <div class="info-box">
                <div class="label">الحالة</div>
                <div class="value">
                    @switch($order->status)
                        @case('DRAFT') مسودة @break
                        @case('PENDING') قيد الاعتماد @break
                        @case('APPROVED') معتمد @break
                        @case('EXECUTED') منفَّذ @break
                        @case('REJECTED') مرفوض @break
                        @case('CANCELLED') ملغي @break
                    @endswitch
                </div>
            </div>
            <div class="info-box">
                <div class="label">المبلغ الإجمالي</div>
                <div class="value">{{ number_format($order->amount, 2) }}</div>
            </div>
            <div class="info-box">
                <div class="label">تاريخ الإنشاء</div>
                <div class="value">{{ $order->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <div class="info-box">
                <div class="label">المنشئ</div>
                <div class="value">{{ $order->creator->name ?? '—' }}</div>
            </div>
            @if ($order->type === 'receipt' && $order->payer_name)
                <div class="info-box">
                    <div class="label">اسم الدافع</div>
                    <div class="value" style="color: #2563eb; font-weight: 700;">{{ $order->payer_name }}</div>
                </div>
            @endif
            @if ($order->approved_by)
                <div class="info-box">
                    <div class="label">اعتمد بواسطة</div>
                    <div class="value">{{ $order->approver->name ?? '—' }}</div>
                </div>
            @endif
            @if ($order->executed_by)
                <div class="info-box">
                    <div class="label">نُفّذ بواسطة</div>
                    <div class="value">{{ $order->executor->name ?? '—' }}</div>
                </div>
            @endif
            @if ($order->rejected_by)
                <div class="info-box">
                    <div class="label">رفض بواسطة</div>
                    <div class="value">{{ $order->rejector->name ?? '—' }}</div>
                </div>
            @endif
            @if ($order->cancelled_by)
                <div class="info-box">
                    <div class="label">أُلغي بواسطة</div>
                    <div class="value">{{ $order->canceller->name ?? '—' }}</div>
                </div>
            @endif
        </div>

        @if ($order->description)
            <div class="notes-box">
                <div class="label">الوصف</div>
                <div class="content">{{ $order->description }}</div>
            </div>
        @endif

        @if ($order->notes)
            <div class="notes-box">
                <div class="label">ملاحظات</div>
                <div class="content">{{ $order->notes }}</div>
            </div>
        @endif

        @if ($order->rejection_reason)
            <div class="rejection-box">
                <div class="label">سبب الرفض</div>
                <div class="content">{{ $order->rejection_reason }}</div>
            </div>
        @endif

        <div class="section-title">بنود الطلب</div>
        @if ($order->items->count() > 0)
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 20%;">التصنيف</th>
                        <th style="width: 50%;">الوصف</th>
                        <th style="width: 25%;">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->category->name ?? '—' }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="amount-col">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">الإجمالي</td>
                        <td class="amount-col">{{ number_format($order->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="font-size: 13px; color: #9ca3af;">لا توجد بنود</p>
        @endif

        @if ($order->documents->count() > 0)
            <div class="section-title">الوثائق المرفقة</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 40%;">اسم الملف</th>
                        <th style="width: 20%;">النوع</th>
                        <th style="width: 15%;">الحجم</th>
                        <th style="width: 20%;">تاريخ الرفع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->documents as $index => $doc)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $doc->file_name }}</td>
                            <td>{{ strtoupper($doc->file_type) }}</td>
                            <td>{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                            <td>{{ $doc->uploaded_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="signatures">
            @if ($order->creator)
                <div class="signature-box">
                    <div class="sig-label">المنشئ</div>
                    <div class="sig-name">{{ $order->creator->name }}</div>
                </div>
            @endif
            @if ($order->approver)
                <div class="signature-box">
                    <div class="sig-label">المعتمد</div>
                    <div class="sig-name">{{ $order->approver->name }}</div>
                </div>
            @endif
            @if ($order->executor)
                <div class="signature-box">
                    <div class="sig-label">المنفِّذ</div>
                    <div class="sig-name">{{ $order->executor->name }}</div>
                </div>
            @endif
        </div>

        <div class="footer">
            تم إنشاء هذا التقرير من نظام إدارة الصندوق النقدي — {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
