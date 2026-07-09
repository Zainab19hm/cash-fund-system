<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>تقرير الطلب — {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', 'Noto Sans Arabic', sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.5;
            direction: rtl;
        }

        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 8px; font-size: 10pt; }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 { font-size: 18pt; margin-bottom: 3px; }
        .header .sub { font-size: 10pt; color: #555; }

        .info-table td {
            border: 1px solid #ccc;
            padding: 5px 8px;
        }

        .info-table .lbl {
            background-color: #f0f0f0;
            font-weight: 600;
            width: 25%;
            font-size: 9pt;
            color: #444;
        }

        .info-table .val {
            width: 25%;
            font-size: 10pt;
        }

        .section-title {
            font-size: 12pt;
            font-weight: 700;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ccc;
        }

        .items-table th {
            background-color: #e8e8e8;
            border: 1px solid #bbb;
            padding: 6px 8px;
            font-size: 9pt;
            font-weight: 600;
            text-align: right;
        }

        .items-table td {
            border: 1px solid #ccc;
            padding: 5px 8px;
            font-size: 10pt;
        }

        .items-table .total td {
            background-color: #f0f0f0;
            font-weight: 700;
        }

        .notes {
            border: 1px solid #ddd;
            padding: 8px 10px;
            margin-bottom: 12px;
            background-color: #f9f9f9;
        }

        .notes .lbl { font-size: 9pt; color: #666; margin-bottom: 2px; }

        .sig-table td {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #999;
            width: 33%;
        }

        .sig-table .sig-lbl { font-size: 8pt; color: #666; }
        .sig-table .sig-name { font-size: 10pt; font-weight: 600; }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الطلب</h1>
        <div class="sub">نظام إدارة الصندوق النقدي</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="lbl">رقم الطلب</td>
            <td class="val">{{ $order->order_number }}</td>
            <td class="lbl">التاريخ</td>
            <td class="val">{{ $order->order_date->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td class="lbl">النوع</td>
            <td class="val">{{ $order->type === 'payment' ? 'صرف' : 'قبض' }}</td>
            <td class="lbl">الحالة</td>
            <td class="val">
                @switch($order->status)
                    @case('DRAFT') مسودة @break
                    @case('PENDING') قيد الاعتماد @break
                    @case('APPROVED') معتمد @break
                    @case('EXECUTED') منفَّذ @break
                    @case('REJECTED') مرفوض @break
                    @case('CANCELLED') ملغي @break
                @endswitch
            </td>
        </tr>
        <tr>
            <td class="lbl">المبلغ الإجمالي</td>
            <td class="val">{{ number_format($order->amount, 2) }}</td>
            <td class="lbl">تاريخ الإنشاء</td>
            <td class="val">{{ $order->created_at->format('Y-m-d H:i') }}</td>
        </tr>
        <tr>
            <td class="lbl">المنشئ</td>
            <td class="val">{{ $order->creator->name ?? '—' }}</td>
            <td class="lbl">اعتمد بواسطة</td>
            <td class="val">{{ $order->approver->name ?? '—' }}</td>
        </tr>
        @if ($order->executed_by || $order->rejected_by || $order->cancelled_by)
        <tr>
            <td class="lbl">نُفّذ بواسطة</td>
            <td class="val">{{ $order->executor->name ?? '—' }}</td>
            <td class="lbl">رفض بواسطة</td>
            <td class="val">{{ $order->rejector->name ?? '—' }}</td>
        </tr>
        @endif
    </table>

    @if ($order->description)
        <div class="notes">
            <div class="lbl">الوصف:</div>
            <div>{{ $order->description }}</div>
        </div>
    @endif

    @if ($order->notes)
        <div class="notes">
            <div class="lbl">ملاحظات:</div>
            <div>{{ $order->notes }}</div>
        </div>
    @endif

    @if ($order->rejection_reason)
        <div class="notes" style="border-color: #c00; background-color: #fff0f0;">
            <div class="lbl" style="color: #c00;">سبب الرفض:</div>
            <div style="color: #c00;">{{ $order->rejection_reason }}</div>
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
                        <td style="text-align: left; direction: ltr;">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3" style="text-align: right;">الإجمالي</td>
                    <td style="text-align: left; direction: ltr;">{{ number_format($order->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p style="font-size: 10pt; color: #999;">لا توجد بنود</p>
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

    <table class="sig-table" style="margin-top: 40px;">
        <tr>
            @if ($order->creator)
                <td>
                    <div class="sig-lbl">المنشئ</div>
                    <div class="sig-name">{{ $order->creator->name }}</div>
                </td>
            @endif
            @if ($order->approver)
                <td>
                    <div class="sig-lbl">المعتمد</div>
                    <div class="sig-name">{{ $order->approver->name }}</div>
                </td>
            @endif
            @if ($order->executor)
                <td>
                    <div class="sig-lbl">المنفِّذ</div>
                    <div class="sig-name">{{ $order->executor->name }}</div>
                </td>
            @endif
        </tr>
    </table>

    <div class="footer">
        تم إنشاء هذا التقرير من نظام إدارة الصندوق النقدي — {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
