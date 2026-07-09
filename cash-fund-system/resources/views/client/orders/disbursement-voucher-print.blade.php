<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>{{ $order->type === 'payment' ? 'إذن صرف' : 'إذن قبض' }} — {{ $order->order_number }}</title>
<style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', 'Segoe UI', sans-serif;
            font-size: 14px;
            color: #1a1a1a;
            line-height: 1.6;
            direction: rtl;
            background: #fff;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .page { margin: 0; box-shadow: none; }
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

        .no-print {
            text-align: center;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .no-print button {
            padding: 10px 30px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: #2563eb;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .no-print button:hover { background: #1d4ed8; }

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

        .signatures {
            display: flex;
            justify-content: center;
            margin-top: 50px;
            gap: 60px;
        }

        .signature-box {
            flex: 1;
            max-width: 200px;
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

        .amount-words {
            font-size: 16px;
            font-weight: 700;
            color: #2563eb;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 2px dashed #2563eb;
            border-radius: 8px;
            background: #eff6ff;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">طباعة إذن الصرف</button>
    </div>

    <div class="page">
        <div class="header">
            <h1>{{ $order->type === 'payment' ? 'إذن صرف' : 'إذن قبض' }}</h1>
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
                <div class="value">منفَّذ</div>
            </div>
        </div>

        <div class="amount-words">
            المبلغ الإجمالي: {{ number_format($order->amount, 2) }} {{ $order->type === 'payment' ? 'دينار' : 'دينار' }}
        </div>

        @if ($order->description)
            <div class="notes-box">
                <div class="label">الوصف</div>
                <div class="content">{{ $order->description }}</div>
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

        @if ($order->notes)
            <div class="notes-box">
                <div class="label">ملاحظات</div>
                <div class="content">{{ $order->notes }}</div>
            </div>
        @endif

        <div class="signatures">
            <div class="signature-box">
                <div class="sig-label">المنشئ</div>
                <div class="sig-name">{{ $order->creator->name ?? '—' }}</div>
            </div>
            <div class="signature-box">
                <div class="sig-label">المنفِّذ</div>
                <div class="sig-name">{{ $order->executor->name ?? '—' }}</div>
            </div>
        </div>

        <div class="footer">
            تم إنشاء هذا الإذن من نظام إدارة الصندوق النقدي — {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
