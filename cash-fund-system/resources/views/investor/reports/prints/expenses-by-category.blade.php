<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>الصرف حسب البند — نظام إدارة الصندوق النقدي</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; font-size: 13px; color: #1a1a1a; direction: rtl; background: #fff; }
        @media print { .no-print { display: none !important; } .page { margin: 0; box-shadow: none; } }
        @page { size: A4 landscape; margin: 10mm; }
        .page { max-width: 297mm; margin: 20px auto; padding: 25px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .no-print { text-align: center; padding: 15px; background: #f3f4f6; border-radius: 8px; margin-bottom: 20px; }
        .no-print button { padding: 10px 30px; font-size: 16px; font-weight: 600; color: #fff; background: #2563eb; border: none; border-radius: 8px; cursor: pointer; }
        .header { text-align: center; border-bottom: 3px solid #1a1a1a; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: 700; margin-bottom: 3px; }
        .header .sub { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f3f4f6; border: 1px solid #d1d5db; padding: 8px 10px; font-size: 12px; font-weight: 600; text-align: right; }
        td { border: 1px solid #d1d5db; padding: 7px 10px; font-size: 12px; }
        .total td { background-color: #f9fafb; font-weight: 700; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .big-number { font-size: 36px; font-weight: 700; color: #2563eb; text-align: center; margin: 30px 0; }
        .big-label { font-size: 14px; color: #666; text-align: center; margin-bottom: 5px; }
        .no-data { text-align: center; padding: 40px; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">طباعة</button>
    </div>
    <div class="page">
        <div class="header">
            <h1>الصرف حسب البند</h1>
            <div class="sub">نظام إدارة الصندوق النقدي</div>
        </div>

        @if(isset($filters['from']) && $filters['from'] && isset($filters['to']) && $filters['to'])
            <div style="text-align: center; font-size: 12px; color: #6b7280; margin-bottom: 15px;">
                الفترة: من {{ $filters['from'] }} إلى {{ $filters['to'] }}
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>البند</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $index => $expense)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $expense->name }}</td>
                        <td>{{ number_format($expense->total, 2) }} د.ك</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="no-data">لا توجد بيانات صرف متاحة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
