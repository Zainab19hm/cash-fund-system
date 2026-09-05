<!DOCTYPE html>
<html dir="rtl" lang="ar" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>تم تفعيل حسابك — {{ config('app.name') }}</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            direction: rtl;
            text-align: right;
            padding: 32px 16px;
        }

        .wrapper {
            max-width: 560px;
            margin: 0 auto;
        }

        /* Card */
        .card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            padding: 40px 32px;
            text-align: center;
        }

        .header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            margin-bottom: 16px;
        }

        .header-icon svg {
            width: 32px;
            height: 32px;
            color: #ffffff;
            fill: none;
            stroke: #ffffff;
            stroke-width: 1.5;
        }

        .header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            line-height: 1.4;
        }

        .header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
            margin-top: 6px;
        }

        /* Body */
        .body {
            padding: 36px 32px;
        }

        .greeting {
            font-size: 17px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }

        .intro {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.8;
            margin-bottom: 28px;
        }

        /* Info box */
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }

        .info-box-title {
            font-size: 13px;
            font-weight: 600;
            color: #1d4ed8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #dbeafe;
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            color: #6b7280;
            font-weight: 500;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 9999px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 600;
        }

        /* CTA button */
        .cta-wrap {
            text-align: center;
            margin-bottom: 28px;
        }

        .cta-button {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        /* Notice */
        .notice {
            background: #fefce8;
            border: 1px solid #fde047;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 13px;
            color: #713f12;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .notice strong {
            font-weight: 700;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }

        .closing {
            font-size: 14px;
            color: #4b5563;
            line-height: 1.7;
        }

        /* Footer */
        .footer {
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 20px 32px;
            text-align: center;
        }

        .footer p {
            font-size: 12px;
            color: #9ca3af;
            line-height: 1.6;
        }

        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">

        {{-- Header --}}
        <div class="header">
            <div class="header-icon">
                <svg viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>
            <h1>تم تفعيل حسابك بنجاح!</h1>
            <p>{{ config('app.name') }}</p>
        </div>

        {{-- Body --}}
        <div class="body">

            <p class="greeting">مرحباً {{ $user->name }}،</p>

            <p class="intro">
                يسعدنا إخبارك بأن حسابك في منظومة <strong>{{ config('app.name') }}</strong> قد تم مراجعته
                والموافقة عليه من قِبل مدير النظام. يمكنك الآن تسجيل الدخول والوصول إلى لوحة التحكم
                الخاصة بك.
            </p>

            {{-- Account details --}}
            <div class="info-box">
                <div class="info-box-title">تفاصيل الحساب</div>

                <div class="info-row">
                    <span class="info-label">الاسم</span>
                    <span class="info-value">{{ $user->name }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">اسم المستخدم</span>
                    <span class="info-value" style="direction: ltr; text-align: left;">{{ $user->username }}</span>
                </div>

                @if($user->email)
                <div class="info-row">
                    <span class="info-label">البريد الإلكتروني</span>
                    <span class="info-value" style="direction: ltr; text-align: left;">{{ $user->email }}</span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">الدور</span>
                    <span class="info-value">
                        @if($user->role === 'admin') مدير
                        @elseif($user->role === 'investor') مستثمر
                        @else عميل
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">حالة الحساب</span>
                    <span class="badge">✓ نشط</span>
                </div>
            </div>

            {{-- CTA --}}
            <div class="cta-wrap">
                <a href="{{ config('app.url') }}/login" class="cta-button">
                    تسجيل الدخول الآن
                </a>
            </div>

            {{-- Security notice --}}
            <div class="notice">
                <strong>ملاحظة أمنية:</strong> إذا لم تقم بإنشاء هذا الحساب أو كنت لا تعرف عنه شيئاً،
                يرجى تجاهل هذه الرسالة والتواصل مع مدير النظام فوراً.
            </div>

            <hr class="divider">

            <p class="closing">
                مع تحيات فريق <strong>{{ config('app.name') }}</strong>،<br>
                إذا واجهتك أي مشكلة في تسجيل الدخول، تواصل مع المدير مباشرةً.
            </p>

        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                هذه رسالة آلية من نظام {{ config('app.name') }}. الرجاء عدم الرد عليها مباشرةً.<br>
                &copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
            </p>
        </div>

    </div>
</div>
</body>
</html>
