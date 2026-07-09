<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>{{ $order->type === 'payment' ? 'إذن صرف' : 'إذن قبض' }} — {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12pt;
            color: #1a1a1a;
            line-height: 1.6;
            direction: rtl;
        }

        .container {
            width: 100%;
            padding: 20mm 15mm;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 22pt;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 11pt;
            color: #555;
        }

        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }

        .info-box {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .info-box .label {
            font-size: 9pt;
            color: #666;
            margin-bottom: 2px;
        }

        .info-box .value {
            font-size: 11pt;
            font-weight: 600;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 8px 10px;
            font-size: 10pt;
            font-weight: 600;
            text-align: right;
        }

        .items-table td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            font-size: 10pt;
        }

        .items-table .total-row td {
            background-color: #f8f8f8;
            font-weight: 700;
            font-size: 11pt;
        }

        .items-table .amount-col {
            text-align: left;
            direction: ltr;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            gap: 30px;
        }

        .signature-box {
            flex: 1;
            text-align: center;
            border-top: 1px solid #999;
            padding-top: 8px;
        }

        .signature-box .sig-label {
            font-size: 9pt;
            color: #666;
            margin-bottom: 3px;
        }

        .signature-box .sig-name {
            font-size: 11pt;
            font-weight: 600;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>{{ $order->type === 'payment' ? 'إذن صرف' : 'إذن قبض' }}</h1>
            <div class="subtitle">نظام إدارة الصندوق النقدي</div>
        </div>

        {{-- Order Info --}}
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

        {{-- Description --}}
        @if ($order->description)
            <div style="margin-bottom: 15px;">
                <span style="font-size: 9pt; color: #666;">الوصف:</span>
                <span style="font-size: 11pt;">{{ $order->description }}</span>
            </div>
        @endif

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">التصنيف</th>
                    <th style="width: 45%;">الوصف</th>
                    <th style="width: 25%; text-align: left; direction: ltr;">المبلغ</th>
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
                    <td colspan="3" style="text-align: right; font-weight: 700;">الإجمالي</td>
                    <td class="amount-col">{{ number_format($order->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Notes --}}
        @if ($order->notes)
            <div style="margin-bottom: 15px;">
                <span style="font-size: 9pt; color: #666;">ملاحظات:</span>
                <span style="font-size: 11pt;">{{ $order->notes }}</span>
            </div>
        @endif

        {{-- Signatures --}}
        <div class="signatures">
            <div class="signature-box">
                <div class="sig-label">من نفَّذ</div>
                <div class="sig-name">{{ $order->executor->name ?? '—' }}</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            تم إنشاء هذا الإذن من نظام إدارة الصندوق النقدي — {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
