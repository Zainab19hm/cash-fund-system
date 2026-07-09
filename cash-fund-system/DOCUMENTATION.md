# Cash Fund System — التوثيق الكامل

## دليل تقني شامل لنظام صندوق النقد

---

## جدول المحتويات

1. [نظرة عامة على المشروع](#1-نظرة-عامة-على-المشروع)
2. [متطلبات التشغيل](#2-متطلبات-التشغيل)
3. [تثبيت وتشغيل المشروع](#3-تثبيت-وتشغيل-المشروع)
4. [بنية المشروع](#4-بنية-المشروع)
5. [قاعدة البيانات](#5-قاعدة-البيانات)
6. [نظام المصادقة والصلاحيات](#6-نظام-المصادقة-والصلاحيات)
7. [مسارات التطبيق Routes](#7-مسارات-التطبيق-routes)
8. [Models والعلاقات](#8-Models-والعلاقات)
9. [Services — طبقة المنطق](#9-Services-طبقة-المنطق)
10. [دورة حياة الطلب](#10-دورة-حياة-الطلب)
11. [نظام التقارير](#11-نظام-التقارير)
12. [نظام الرسائل والإشعارات](#12-نظام-الرسائل-والإشعارات)
13. [نظام إدارة الوثائق](#13-نظام-إدارة-الوثائق)
14. [سجل التدقيق Audit Trail](#14-سجل-التدقيق-audit-trail)
15. [واجهة المستخدم والتصميم](#15-واجهة-المستخدم-والتصميم)
16. [نظام الألوان والسمات Themes](#16-نظام-الألوان-والسمات-themes)
17. [الطباعة وتوليد PDF](#17-الطباعة-وتوليد-pdf)
18. [الأمان وأفضل الممارسات](#18-الأمان-وأفضل-الممارسات)
19. [إخطارات الشبكة Routes API](#19-إخطارات-الشبكة-routes-api)
20. [الأوامر المتاحة artisan](#20-الأوامر-المتاحة-artisan)

---

## 1. نظرة عامة على المشروع

**الاسم:** Cash Fund System (نظام صندوق النقد)

**الغرض:** نظام إلكتروني لإدارة الصندوق النقدي — يتيح إنشاء طلبات صرف وقبض، ومتابعتها عبر دورة عمل متعددة المراحل، مع تقارير مالية شاملة وسجل تدقيق كامل.

**اللغة:**واجهة المستخدم بالعربية بالكامل (RTL)

**المطور:** تم بناؤه باستخدام:

| التقنية | الإصدار |
|---------|--------|
| **PHP** | ^8.0 |
| **Laravel** | ^9.0 |
| **MySQL** | — |
| **Tailwind CSS** | ^3.4.19 |
| **Alpine.js** | ^3.15.12 |
| **DomPDF** | * (barryvdh/laravel-dompdf) |

**الرخصة:** MIT

---

## 2. متطلبات التشغيل

### متطلبات الخادم
- PHP >= 8.0 (مع البينات المطلوبة: `bcmath`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`)
- MySQL >= 5.7 أو MariaDB >= 10.3
- Composer >= 2.0
- Node.js >= 14.x + npm
- Apache أو Nginx

### حزم Composer الرئيسية
| الحزمة | الغرض |
|--------|-------|
| `laravel/framework ^9.0` | إطار العمل الأساسي |
| `laravel/sanctum ^2.14` | مصادقة API (مثبت لكن الاستخدام محدود) |
| `barryvdh/laravel-dompdf` | توليد ملفات PDF |
| `spatie/laravel-permission ^6.25` | إدارة الأدوار والصلاحيات (مثبت، لكن التطبيق يستخدم تنفيذاً خفيفاً مخصصاً) |
| `fruitcake/laravel-cors ^2.0.5` | التعامل مع CORS |
| `guzzlehttp/guzzle ^7.2` | عميل HTTP |

### حزم npm الرئيسية
| الحزمة | الغرض |
|--------|-------|
| `tailwindcss ^3.4.19` | إطار CSS |
| `alpinejs ^3.15.12` | تفاعل خفيف في الواجهة |
| `axios ^0.25` | طلبات HTTP |
| `laravel-mix ^6.0.6` | تجميع الأصول |
| `postcss` + `autoprefixer` | معالجة CSS |

---

## 3. تثبيت وتشغيل المشروع

### 3.1 خطوات التثبيت

```bash
# 1. استنساخ المستودع
git clone <repository-url>
cd cash-fund-system

# 2. تثبيت الحزم
composer install
npm install

# 3. إعداد ملف البيئة
cp .env.example .env
php artisan key:generate

# 4. إعداد قاعدة البيانات
# عدّل ملف .env بالبيانات التالية:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=cash_fund_system
# DB_USERNAME=root
# DB_PASSWORD=

# 5. إنشاء قاعدة البيانات
php artisan migrate

# 6. تشغيل البذور (Seeding)
php artisan db:seed

# 7. تجميع الأصول
npm run dev
# أو للإنتاج:
npm run prod

# 8. تشغيل الخادم
php artisan serve
```

### 3.2 بيانات الدخول الافتراضية

| الحقل | القيمة |
|-------|--------|
| **اسم المستخدم** | `admin` |
| **كلمة المرور** | `password` |

### 3.3 إعداد ملف `.env`

```env
APP_NAME="Cash Fund System"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cash_fund_system
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
```

---

## 4. بنية المشروع

```
cash-fund-system/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php
│   │   │   ├── Admin/
│   │   │   │   ├── UserController.php
│   │   │   │   ├── PermissionController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── Client/
│   │   │   │   ├── OrderController.php
│   │   │   │   └── OrderPdfController.php
│   │   │   └── Investor/
│   │   │       └── ReportController.php
│   │   └── Middleware/
│   │       └── RoleMiddleware.php          # middleware مخصص للأدوار
│   ├── Models/
│   │   ├── User.php
│   │   ├── OrderFund.php
│   │   ├── OrderItem.php
│   │   ├── Category.php
│   │   ├── Document.php
│   │   ├── DailyMovement.php
│   │   ├── LogAudit.php
│   │   └── Notification.php
│   └── Services/
│       ├── OrderService.php
│       ├── ReportService.php
│       ├── DocumentService.php
│       ├── NotificationService.php
│       └── OrderNumberService.php
├── config/
│   └── permission.php              # إعدادات Spatie Permission
├── database/
│   ├── migrations/                 # 19 ملف ترحيل
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── CategorySeeder.php
├── resources/
│   ├── css/
│   │   ├── app.css
│   │   └── themes.css              # نظام السمات (3 أدوار × 2 سمة)
│   ├── js/
│   │   ├── app.js                  # تهيئة Alpine.js
│   │   └── bootstrap.js            # Lodash, Axios
│   └── views/
│       ├── layouts/app.blade.php   # التخطيط الرئيسي RTL
│       ├── components/             # مكونات Blade
│       ├── auth/login.blade.php
│       ├── admin/                  # صفحات المدير
│       ├── client/                 # صفحات العميل
│       ├── investor/               # صفحات المستثمر
│       └── notifications/
├── routes/
│   ├── web.php                     # جميع مسارات الويب
│   └── api.php                     # نقاط نهاية API (محدودة)
├── tailwind.config.js
├── webpack.mix.js
└── postcss.config.js
```

---

## 5. قاعدة البيانات

### 5.1 جدول المستخدمين `users`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `name` | varchar(100) | الاسم الكامل |
| `national_id` | varchar(20) | رقم الهوية (فريد) |
| `employee_number` | varchar(20) | رقم الموظف (فريد) |
| `phone` | varchar(20) | رقم الجوال (اختياري) |
| `position` | varchar(100) | المنصب الوظيفي (اختياري) |
| `username` | varchar(100) | اسم المستخدم للدخول (فريد) |
| `password` | varchar | كلمة المرور (مشفرة bcrypt) |
| `role` | enum('admin','investor','client') | الدور |
| `is_active` | boolean | هل الحساب مفعل (افتراضي: true) |
| `last_login_at` | timestamp | آخر مرة سجل الدخول فيها |
| `created_at`, `updated_at` | timestamps | — |

### 5.2 جدول طلبات الصندوق `orders_fund`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `order_number` | varchar(20) | رقم الطلب (فريد) — الصيغة: `ORD-YYYY-NNN` |
| `type` | enum('payment','receipt') | نوع الطلب: صرف أو قبض |
| `amount` | decimal(15,2) | المبلغ الإجمالي |
| `description` | text | وصف الطلب (اختياري) |
| `payer_name` | varchar(255) | اسم الدافع — إلزامي لطلبات القبض |
| `status` | enum | حالة الطلب (انظر دورة الحياة) |
| `order_date` | date | تاريخ الطلب |
| `created_by` | FK → users | منشئ الطلب |
| `approved_by` | FK → users | المُعتمد (اختياري) |
| `approved_at` | timestamp | تاريخ الاعتماد |
| `rejected_by` | FK → users | الرافض (اختياري) |
| `rejection_reason` | text | سبب الرفض |
| `executed_by` | FK → users | المنفذ (اختياري) |
| `executed_at` | timestamp | تاريخ التنفيذ |
| `cancelled_by` | FK → users | المُلغي (اختياري) |
| `notes` | text | ملاحظات (اختياري) |
| `created_at`, `updated_at` | timestamps | — |

### 5.3 جدول بنود الطلب `order_items`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `order_id` | FK → orders_fund | الطلب المرتبط (حذف مقيد) |
| `category_id` | FK → categories | التصنيف |
| `description` | varchar(255) | وصف البند |
| `amount` | decimal(15,2) | مبلغ البند |
| `created_at`, `updated_at` | timestamps | — |

### 5.4 جدول التصنيفات `categories`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `name` | varchar(100) | اسم التصنيف |
| `type` | enum('payment','receipt','both') | نوع التصنيف |
| `is_active` | boolean | هل التصنيف مفعل |
| `created_at`, `updated_at` | timestamps | — |

**ملاحظة:** يوجد تقييد فريد على (`name`, `type`).

### 5.5 جدول الوثائق `documents`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `order_id` | FK → orders_fund | الطلب المرتبط (حذف مقيد) |
| `file_name` | varchar(255) | اسم الملف الأصلي |
| `file_path` | varchar(500) | مسار التخزين |
| `file_type` | varchar(50) | امتداد الملف |
| `file_size` | integer | حجم الملف بالبايت |
| `uploaded_by` | FK → users | من رفع الملف |
| `uploaded_at` | timestamp | تاريخ الرفع |
| `created_at`, `updated_at` | timestamps | — |

### 5.6 جدول الحركات اليومية `daily_movements`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `order_id` | FK → orders_fund | الطلب المرتبط (حذف مقيد) |
| `movement_type` | enum('payment','receipt') | نوع الحركة |
| `amount` | decimal(15,2) | مبلغ الحركة |
| `balance_after` | decimal(15,2) | الرصيد بعد الحركة |
| `movement_date` | date | تاريخ الحركة |
| `executed_at` | timestamp | وقت التنفيذ |
| `created_at`, `updated_at` | timestamps | — |

### 5.7 جدول الإشعارات `notifications`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `user_id` | FK → users | المستخدم المستهدف (حذف متتالي) |
| `order_id` | FK → orders_fund | الطلب المرتبط (حذف متتالي) |
| `type` | enum('APPROVED','REJECTED','EXECUTED','NEW_ORDER') | نوع الإشعار |
| `message` | varchar(500) | نص الإشعار |
| `is_read` | boolean | هل تم قراءته |
| `read_at` | timestamp | وقت القراءة |
| `created_at`, `updated_at` | timestamps | — |

### 5.8 جدول سجل التدقيق `log_audit`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `user_id` | FK → users | المستخدم الذي نفّذ العملية (حذف متتالي) |
| `action` | varchar(50) | نوع العملية: create, update, login, toggle_status, reset_password |
| `entity_type` | varchar(50) | نوع الكيان: users, categories, role_permissions |
| `entity_id` | bigint | معرف الكيان |
| `notes` | varchar(255) | ملاحظات (اختياري) |
| `created_at` | timestamp | — |

### 5.9 جدول الصلاحيات `permissions`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `key` | varchar | مفتاح الصلاحية (فريد) |
| `label` | varchar | التسمية بالعربية |

### 5.10 جدول صلاحيات الأدوار `role_permissions`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `role` | enum('admin','investor','client') | الدور |
| `permission_id` | FK → permissions | الصلاحية (حذف متتالي) |

**ملاحظة:** تقييد فريد على (`role`, `permission_id`).

### 5.11 جدول تسلسل أرقام الطلبات `order_number_sequences`

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | bigint PK | المعرف الوحيد |
| `year` | integer | السنة (فريد) |
| `last_number` | integer | آخر رقم مستخدم (افتراضي: 0) |
| `created_at`, `updated_at` | timestamps | — |

### 5.12 جداول Laravel القياسية
- `password_resets`
- `failed_jobs`
- `personal_access_tokens`

### 5.13 بيانات البذور Seeders

#### المستخدم الافتراضي
| الحقل | القيمة |
|-------|--------|
| `name` | System Admin |
| `national_id` | 000000001 |
| `employee_number` | EMP-0001 |
| `username` | admin |
| `password` | password |
| `role` | admin |
| `is_active` | true |

#### الصلاحيات الافتراضية (8 صلاحيات)
| المفتاح | التسمية |
|---------|---------|
| `create_order` | إنشاء طلب |
| `approve_order` | اعتماد طلب |
| `reject_order` | رفض طلب |
| `execute_order` | تنفيذ طلب |
| `cancel_order` | إلغاء طلب |
| `manage_users` | إدارة المستخدمين |
| `manage_permissions` | إدارة الصلاحيات |
| `manage_categories` | إدارة التصنيفات |

#### توزيع الصلاحيات الافتراضي
| الدور | الصلاحيات |
|-------|-----------|
| **Admin** | جميع الصلاحيات ما عدا `create_order` |
| **Client** | `create_order` فقط |
| **Investor** | لا توجد صلاحيات افتراضية (للقراءة فقط) |

#### التصنيفات الافتراضية (7 تصنيفات)
| التصنيف | النوع |
|---------|-------|
| رواتب | صرف (payment) |
| مستلزمات مكتبية | صرف (payment) |
| صيانة | صرف (payment) |
| إيرادات مبيعات | قبض (receipt) |
| دعم مستثمرين | قبض (receipt) |
| خدمات متنوعة | قبض (receipt) |
| تعاون ونشاط | قبض (receipt) |

---

## 6. نظام المصادقة والصلاحيات

### 6.1 آلية المصادقة
- **مصادقة قائمة على الجلسة** باستخدام `Auth::attempt()` المدمج في Laravel
- تسجيل الدخول عبر **اسم المستخدم + كلمة المرور** (وليس البريد الإلكتروني)
- دعم `remember_token` للجلسات المستمرة

### 6.2 middleware الدور `RoleMiddleware`

الملف: `app/Http/Middleware/RoleMiddleware.php`

```
Middleware: role:admin, role:investor, role:client
```

**آلية العمل:**
1. يتحقق من وجود جلسة نشطة (`Auth::check()`)
2. يتحقق من حالة الحساب (`is_active`) — إذا كان معطلاً، يتم تسجيل الخروج وإظهار رسالة خطأ بالعربية
3. يقارن دور المستخدم (`user.role`) بالدور المطلوب — إذا لم يتطابق، يُعيد خطأ 403

### 6.3 تدفق تسجيل الدخول

```
GET /login  →  عرض نموذج الدخول (middleware: guest)
POST /login →  التحقق من البيانات →  تسجيل في سجل التدقيق →  تحديث last_login_at
             →  التوجيه حسب الدور:
                 - admin     → admin.reports.dashboard
                 - investor  → investor.dashboard
                 - client    → client.orders.index
POST /logout →  إلغاء الجلسة
```

### 6.4 حماية المسارات

| المجموعة | Middleware |
|----------|-----------|
| صفحات تسجيل الدخول | `guest` |
| صفحات المدير | `auth`, `role:admin` |
| صفحات المستثمر | `auth`, `role:investor` |
| صفحات العميل | `auth`, `role:client` |
| الإشعارات | `auth` |

### 6.5 الصلاحيات القائمة على الأدوار

| الصلاحية | Admin | Investor | Client |
|----------|-------|----------|--------|
| إنشاء طلب | ✗ | ✗ | ✓ |
| اعتماد طلب | ✓ | ✗ | ✗ |
| رفض طلب | ✓ | ✗ | ✗ |
| تنفيذ طلب | ✓ | ✗ | ✗ |
| إلغاء طلب | ✓ | ✗ | ✗ |
| إدارة المستخدمين | ✓ | ✗ | ✗ |
| إدارة الصلاحيات | ✓ | ✗ | ✗ |
| إدارة التصنيفات | ✓ | ✗ | ✗ |

---

## 7. مسارات التطبيق Routes

### 7.1 مسارات المصادقة (لغير المسجلين)

| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/` | إعادة توجيه إلى صفحة الدخول |
| GET | `/login` | نموذج تسجيل الدخول |
| POST | `/login` | معالجة تسجيل الدخول |
| POST | `/logout` | تسجيل الخروج |

### 7.2 مسارات المدير `/admin`

#### إدارة المستخدمين
| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/admin/users` | قائمة المستخدمين (صفحات، بحث، تصفية) |
| GET | `/admin/users/create` | نموذج إنشاء مستخدم |
| POST | `/admin/users` | حفظ مستخدم جديد |
| GET | `/admin/users/{user}/edit` | نموذج تعديل المستخدم |
| PUT | `/admin/users/{user}` | تحديث المستخدم |
| POST | `/admin/users/{user}/reset-password` | إعادة تعيين كلمة المرور |
| POST | `/admin/users/{user}/toggle-status` | تفعيل/تعطيل الحساب |

#### إدارة الصلاحيات
| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/admin/permissions` | عرض مصفوفة الصلاحيات |
| POST | `/admin/permissions/update` | تحديث صلاحيات الأدوار (دفعة واحدة) |

#### إدارة التصنيفات
| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/admin/categories` | قائمة التصنيفات |
| GET | `/admin/categories/create` | نموذج إنشاء تصنيف |
| POST | `/admin/categories` | حفظ تصنيف جديد |
| GET | `/admin/categories/{category}/edit` | نموذج تعديل التصنيف |
| PUT | `/admin/categories/{category}` | تحديث التصنيف |
| POST | `/admin/categories/{category}/toggle-status` | تفعيل/تعطيل التصنيف |

#### إدارة الطلبات (المدير)
| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/admin/orders` | جميع الطلبات (ما عدا DRAFT) |
| GET | `/admin/orders/{order}` | تفاصيل الطلب |
| GET | `/admin/orders/{order}/report` | تقرير الطلب للطباعة |
| GET | `/admin/orders/{order}/documents/{document}/download` | تحميل وثيقة |
| POST | `/admin/orders/{order}/approve` | اعتماد الطلب |
| POST | `/admin/orders/{order}/reject` | رفض الطلب (مع سبب) |
| POST | `/admin/orders/{order}/execute` | تنفيذ الطلب |
| POST | `/admin/orders/{order}/cancel` | إلغاء الطلب |

#### التقارير (المدير) — 14 تقرير
| Method | URI | اسم التقرير |
|--------|-----|-------------|
| GET | `/admin/reports/dashboard` | لوحة معلومات الطلبات |
| GET | `/admin/reports/daily-journal` | يومية الصندوق |
| GET | `/admin/reports/orders-status` | كشف الأوامر وحالتها |
| GET | `/admin/reports/order-items/{order}` | تفصيل بنود الأمر |
| GET | `/admin/reports/missing-documents` | الوثائق الناقصة |
| GET | `/admin/reports/user-activity` | نشاط المستخدمين |
| GET | `/admin/reports/current-balance` | الرصيد الحالي |
| GET | `/admin/reports/movement-statement` | كشف الحركة |
| GET | `/admin/reports/totals` | إجمالي الصرف والقبض |
| GET | `/admin/reports/expenses-by-category` | الصرف حسب التصنيف |
| GET | `/admin/reports/documents-archive` | حالة أرشفة الوثائق |
| GET | `/admin/reports/permissions-report` | تقرير الصلاحيات |
| GET | `/admin/reports/audit-trail` | سجل التدقيق |

#### طباعة التقارير (المدير) — 11 مسار
| Method | URI |
|--------|-----|
| GET | `/admin/reports/daily-journal/print` |
| GET | `/admin/reports/orders-status/print` |
| GET | `/admin/reports/missing-documents/print` |
| GET | `/admin/reports/user-activity/print` |
| GET | `/admin/reports/current-balance/print` |
| GET | `/admin/reports/movement-statement/print` |
| GET | `/admin/reports/totals/print` |
| GET | `/admin/reports/expenses-by-category/print` |
| GET | `/admin/reports/documents-archive/print` |
| GET | `/admin/reports/permissions-report/print` |
| GET | `/admin/reports/audit-trail/print` |

### 7.3 مسارات المستثمر `/investor`

| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/investor/dashboard` | لوحة معلومات المستثمر (الرصيد + العدادات) |
| GET | `/investor/current-balance` | الرصيد الحالي |
| GET | `/investor/movement-statement` | كشف الحركة (لفترة) |
| GET | `/investor/totals` | إجمالي الصرف والقبض |
| GET | `/investor/expenses-by-category` | الصرف حسب التصنيف |
| GET | `/investor/pending-orders` | الطلبات المعلقة |

#### طباعة التقارير (المستثمر) — 5 مسارات
| Method | URI |
|--------|-----|
| GET | `/investor/current-balance/print` |
| GET | `/investor/movement-statement/print` |
| GET | `/investor/totals/print` |
| GET | `/investor/expenses-by-category/print` |
| GET | `/investor/pending-orders/print` |

### 7.4 مسارات العميل `/client`

| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/client/orders` | طلباتي |
| GET | `/client/orders/create` | إنشاء طلب جديد |
| POST | `/client/orders` | حفظ الطلب (مسودة) |
| GET | `/client/orders/{order}` | تفاصيل الطلب |
| POST | `/client/orders/{order}/submit` | إرسال للموافقة |
| POST | `/client/orders/{order}/upload-document` | رفع وثيقة |
| POST | `/client/orders/{order}/cancel` | إلغاء الطلب |
| GET | `/client/orders/{order}/disbursement-voucher` | سند صرف (PDF) |
| GET | `/client/orders/{order}/receipt-voucher` | سند قبض (PDF) |
| GET | `/client/account-statement` | كشف حسابي |

### 7.5 مسارات الإشعارات (مشتركة)

| Method | URI | الوصف |
|--------|-----|-------|
| GET | `/notifications` | قائمة الإشعارات (صفحات) |
| POST | `/notifications/{notification}/read` | تحديد كمقروء |
| GET | `/notifications/unread-count` | عدد غير المقروءين (JSON — AJAX) |

---

## 8. Models والعلاقات

### 8.1 Model: User

```
 relationships:
   - hasMany(OrderFund, 'created_by')     → الطلبات التي أنشأها
   - hasMany(OrderFund, 'approved_by')    → الطلبات التي اعتمدها
   - hasMany(OrderFund, 'executed_by')    → الطلبات التي نفذها
   - hasMany(Notification, 'user_id')     → إشعاراته
   - hasMany(LogAudit, 'user_id')         → سجل تدقيقه
```

### 8.2 Model: OrderFund

```
relationships:
   - belongsTo(User, 'created_by')   → creator
   - belongsTo(User, 'approved_by')  → approver
   - belongsTo(User, 'rejected_by')  → rejector
   - belongsTo(User, 'executed_by')  → executor
   - belongsTo(User, 'cancelled_by') → canceller
   - hasMany(OrderItem, 'order_id')  → items
   - hasMany(Document, 'order_id')   → documents
```

**الجداول المحولة (`casts`):**
- `amount` → `decimal:2`
- `order_date` → `date`
- `approved_at` → `datetime`
- `executed_at` → `datetime`

### 8.3 Model: OrderItem

```
relationships:
   - belongsTo(OrderFund, 'order_id')
   - belongsTo(Category, 'category_id')
```

### 8.4 Model: Category

```
relationships:
   - hasMany(OrderItem, 'category_id')
```

### 8.5 Model: Document

```
relationships:
   - belongsTo(OrderFund, 'order_id')
   - belongsTo(User, 'uploaded_by')
```

### 8.6 Model: DailyMovement

```
relationships:
   - belongsTo(OrderFund, 'order_id')
```

### 8.7 Model: Notification

```
relationships:
   - belongsTo(User, 'user_id')
   - belongsTo(OrderFund, 'order_id')

 scopes:
   - unread()  → where('is_read', false)
```

### 8.8 Model: LogAudit

```
relationships:
   - belongsTo(User, 'user_id')
```

---

## 9. Services — طبقة المنطق

### 9.1 OrderService

**الملف:** `app/Services/OrderService.php`

**المهام:**

| الدالة | الوصف |
|--------|-------|
| `createDraft($data, $items, $clientId)` | إنشاء مسودة طلب مع البنود |
| `submitForApproval($order)` | إرسال الطلب للموافقة |
| `approve($order, $approvedBy)` | اعتماد الطلب |
| `reject($order, $rejectedBy, $reason)` | رفض الطلب مع سبب |
| `cancel($order, $cancelledBy)` | إلغاء الطلب |
| `execute($order, $executedBy)` | تنفيذ الطلب وتحديث الرصيد |

**تفاصيل التنفيذ:**

- **`createDraft`:**
  - يتحقق من إلزامية `payer_name` لطلبات القبض
  - يتحقق من تطابق مجموع بنود الطلب مع الإجمالي باستخدام `bcmath` (`bcadd`/`bccomp`)
  - يتحقق من توافق تصنيفات البنود مع نوع الطلب
  - ينشئ الطلب داخل معاملة قاعدة بيانات (`DB::transaction`)
  - يولّد رقم الطلب تلقائياً عبر `OrderNumberService`

- **`execute`:**
  - يستخدم `lockForUpdate()` لقفل صف آخر حركة لمنع سباق العمليات
  - يحسب الرصيد الجديد: `balance_after = currentBalance + delta` (delta = المبلغ للقبض، -المبلغ للصرف)
  - ينشئ سجل حركة يومية (`DailyMovement`)
  - يرسل إشعاراً للعميل

### 9.2 OrderNumberService

**الملف:** `app/Services/OrderNumberService.php`

**الدالة:** `generate() → string`

**آلية العمل:**
1. يضمن وجود صف التسلسل للسنة الحالية عبر `insertOrIgnore`
2. يقفل الصف بـ `lockForUpdate()` لمنع التزامن
3. يزيد العداد بمقدار 1
4. يُعيد الرقم بصيغة `ORD-YYYY-NNN` (مثال: `ORD-2026-001`)

### 9.3 ReportService

**الملف:** `app/Services/ReportService.php`

| الدالة | الرمز | الوصف |
|--------|-------|-------|
| `dailyJournal($date)` | RPT-01 | حركات يوم معين |
| `ordersWithStatus($status, $from, $to)` | RPT-02 | طلبات حسب الحالة |
| `orderItemsDetail($orderId)` | RPT-03 | بنود طلب معين |
| `missingDocuments()` | RPT-04 | طلبات بدون وثائق |
| `userActivity($userId, $from, $to)` | RPT-05 | نشاط المستخدمين |
| `currentBalance()` | RPT-06 | الرصيد الحالي |
| `movementStatement($from, $to)` | RPT-07 | كشف حركة لفترة |
| `totalsByType($from, $to)` | RPT-08 | إجمالي الصرف والقبض |
| `expensesByCategory($from, $to)` | RPT-09 | الصرف حسب التصنيف |
| `documentsArchiveStatus()` | RPT-10 | حالة أرشفة الوثائق |
| `permissionsReport()` | RPT-11 | تقرير الصلاحيات |
| `pendingOrdersReport()` | RPT-12 | الطلبات المعلقة |
| `ordersDashboard()` | RPT-13 | عدادات الطلبات |
| `auditTrailReport()` | RPT-14 | سجل التدقيق |

### 9.4 DocumentService

**الملف:** `app/Services/DocumentService.php`

**الدالة:** `upload($order, $file, $userId) → Document`

**قيود الرفع:**
- **الامتدادات المسموحة:** pdf, jpg, jpeg, png, docx
- **أنواع MIME المسموحة:** application/pdf, image/jpeg, image/png, application/vnd.openxmlformats-officedocument.wordprocessingml.document
- **الحد الأقصى للحجم:** 10 ميغابايت
- ** места التخزين:** `storage/app/private/documents/{order_id}/` (خارج المجلد العام)
- **حالة الطلب:** لا يمكن الرفع على طلب بحالة EXECUTED, CANCELLED, أو REJECTED

### 9.5 NotificationService

**الملف:** `app/Services/NotificationService.php`

| الدالة | الوصف |
|--------|-------|
| `notify($order, $type)` | إرسال إشعار للعميل (APPROVED, REJECTED, EXECUTED) |
| `notifyAdminsNewOrder($order)` | إرسال إشعار لجميع المديرين النشطين عند طلب جديد |
| `markAsRead($notification, $userId)` | تحديد الإشعار كمقروء |
| `unreadCount($userId)` | عدد الإشعارات غير المقروءة |

**أنواع الإشعارات:**
| النوع | الوصف | المرسل | المستقبل |
|-------|-------|--------|----------|
| `NEW_ORDER` | طلب جديد | النظام | جميع المديرين النشطين |
| `APPROVED` | تم اعتماد الطلب | المدير | صاحب الطلب |
| `REJECTED` | تم رفض الطلب (+ السبب) | المدير | صاحب الطلب |
| `EXECUTED` | تم تنفيذ الطلب | المدير | صاحب الطلب |

---

## 10. دورة حياة الطلب

```
                    ┌─────────────┐
                    │   DRAFT     │  ← يتم الإنشاء كمسودة
                    │  (مسودة)    │
                    └──────┬──────┘
                           │
               ┌───────────┴───────────┐
               │                       │
               ▼                       ▼
        ┌──────────────┐       ┌──────────────┐
        │   SUBMIT     │       │    CANCEL     │
        │ (إرسال)     │       │   (إلغاء)    │
        └──────┬───────┘       └──────────────┘
               │
               ▼
        ┌──────────────┐
        │   PENDING    │  ← قيد الانتظار للموافقة
        │ (قيد الانتظار)│
        └──────┬───────┘
               │
       ┌───────┴───────┐
       │               │
       ▼               ▼
┌──────────────┐ ┌──────────────┐
│  APPROVED    │ │   REJECTED   │
│  (معتمد)    │ │   (مرفوض)   │
└──────┬───────┘ └──────────────┘
       │
       ▼
┌──────────────┐
│  EXECUTED    │  ← يتم تنفيذه وتحديث الرصيد
│  (منفذ)     │
└──────────────┘
```

### حالات الطلب

| الحالة | الوصف | يمكن الإلغاء؟ | يمكن التحديث؟ |
|--------|-------|----------------|----------------|
| `DRAFT` | مسودة — لم يُرسل بعد | ✓ | ✓ |
| `PENDING` | قيد الانتظار — بانتظار اعتماد المدير | ✓ | ✗ |
| `APPROVED` | معتمد — بانتظار التنفيذ | ✗ | ✗ |
| `REJECTED` | مرفوض — مع سبب الرفض | ✗ | ✗ |
| `EXECUTED` | منفذ — تم تحديث الرصيد | ✗ | ✗ |
| `CANCELLED` | ملغي | ✗ | ✗ |

### قيود الانتقال بين الحالات

| العملية | الحالة المطلوبة | قيود إضافية |
|---------|----------------|-------------|
| إنشاء | — | الحالة الافتراضية: DRAFT |
| إرسال للموافقة | DRAFT | يجب أن يحتوي على بنود (≥1) |
| اعتماد | PENDING | لا يمكن لمنشئ الطلب اعتماده بنفسه |
| رفض | PENDING | سبب الرفض إلزامي |
| تنفيذ | PENDING → APPROVED | — |
| إلغاء | DRAFT أو PENDING | — |

### التحقق من مطابقة المبالغ

عند إنشاء الطلب، يتم التحقق من أن مجموع مبالغ البنود يطابق المبلغ الإجمالي للطلب باستخدام مكتبة `bcmath` لتجنب مشاكل الأعداد العشرية:

```php
$itemsTotal = collect($items)->reduce(function ($sum, $item) {
    return bcadd($sum, number_format($item['amount'], 2, '.', ''), 2);
}, '0.00');

if (bccomp($itemsTotal, number_format($data['amount'], 2, '.', ''), 2) !== 0) {
    throw ValidationException::withMessages([...]);
}
```

### حماية سباق العمليات (Concurrency Protection)

عند تنفيذ الطلب، يتم استخدام `lockForUpdate()` لقفل صف آخر حركة يومية:

```php
$lastMovement = DailyMovement::orderByDesc('id')
    ->lockForUpdate()
    ->first();

$currentBalance = $lastMovement ? $lastMovement->balance_after : '0.00';
$newBalance = bcadd($currentBalance, $delta, 2);
```

هذا يضمن أن عمليات التنفيذ المتزامنة لن تتسبب في أخطاء في حساب الرصيد.

---

## 11. نظام التقارير

### 11.1 تقارير المدير (14 تقرير)

#### RPT-01: يومية الصندوق
- **المسار:** `/admin/reports/daily-journal`
- **الوصف:** جميع الحركات (صرف/قبض) ليوم أو فترة معينة
- **الفلاتر:** التاريخ
- **البيانات:** `daily_movements` مع `orders_fund`

#### RPT-02: كشف الأوامر وحالتها
- **المسار:** `/admin/reports/orders-status`
- **الوصف:** جميع الطلبات مع إمكانية التصفية حسب الحالة والتواريخ
- **الفلاتر:** الحالة، من تاريخ، إلى تاريخ
- **البيانات:** `orders_fund` مع `users` (creator)
- **التنسيق:** صفحات (30 عنصر)

#### RPT-03: تفصيل بنود الأمر
- **المسار:** `/admin/reports/order-items/{order}`
- **الوصف:** تفصيل بنود طلب معين
- **البيانات:** `order_items` مع `categories`

#### RPT-04: الوثائق الناقصة
- **المسار:** `/admin/reports/missing-documents`
- **الوصف:** الطلبات بحالات PENDING/APPROVED/EXECUTED بدون أي وثيقة
- **البيانات:** `orders_fund` (doesntHave documents)

#### RPT-05: نشاط المستخدمين
- **المسار:** `/admin/reports/user-activity`
- **الوصف:** سجل العمليات من `log_audit`
- **الفلاتر:** المستخدم، من تاريخ، إلى تاريخ
- **التنسيق:** صفحات (50 عنصر)

#### RPT-06: الرصيد الحالي
- **المسار:** `/admin/reports/current-balance`
- **الوصف:** آخر رصيد مسجل في `daily_movements`

#### RPT-07: كشف الحركة
- **المسار:** `/admin/reports/movement-statement`
- **الوصف:** الحركات خلال فترة معينة
- **الفلاتر:** من تاريخ، إلى تاريخ
- **التنسيق:** صفحات (30 عنصر)

#### RPT-08: إجمالي الصرف والقبض
- **المسار:** `/admin/reports/totals`
- **الوصف:** مجموع مبالغ الصرف ومجموع مبالغ القبض
- **الفلاتر:** من تاريخ، إلى تاريخ

#### RPT-09: الصرف حسب التصنيف
- **المسار:** `/admin/reports/expenses-by-category`
- **الوصف:** تكلفة كل تصنيف من الطلبات المنفذة فقط
- **الفلاتر:** من تاريخ، إلى تاريخ

#### RPT-10: تقرير الوثائق (حالة الأرشفة)
- **المسار:** `/admin/reports/documents-archive`
- **الوصف:** عدد الوثائق لكل طلب
- **التنسيق:** صفحات (30 عنصر)

#### RPT-11: تقرير الصلاحيات
- **المسار:** `/admin/reports/permissions-report`
- **الوصف:** عدد الطلبات المنشأة والمعتمدة لكل مستخدم

#### RPT-12: الطلبات المعلقة (للمستثمر)
- **المسار:** `/investor/pending-orders`
- **الوصف:** جميع الطلبات بحالة PENDING

#### RPT-13: لوحة معلومات الطلبات
- **المسار:** `/admin/reports/dashboard`
- **الوصف:** عدادات الطلبات حسب الحالة (DRAFT, PENDING, APPROVED, REJECTED, EXECUTED, CANCELLED)

#### RPT-14: سجل التدقيق
- **المسار:** `/admin/reports/audit-trail`
- **الوصف:** الطلبات التي لها سجل اعتماد أو رفض مع تفاصيل المنشئ والمعتمد والرافض والمنفذ
- **التنسيق:** صفحات (30 عنصر)

### 11.2 تقارير المستثمر (5 تقارير)

| التقرير | المسار | الوصف |
|---------|--------|-------|
| الرصيد الحالي | `/investor/current-balance` | آخر رصيد مسجل |
| كشف الحركة | `/investor/movement-statement` | الحركات خلال فترة |
| إجمالي الصرف والقبض | `/investor/totals` | مجموعات حسب النوع |
| الصرف حسب التصنيف | `/investor/expenses-by-category` | تكاليف التصنيفات |
| الطلبات المعلقة | `/investor/pending-orders` | طلبات بحالة PENDING |

---

## 12. نظام الرسائل والإشعارات

### 12.1 آلية العمل
- **إشعارات داخلية** (وليست بريد إلكتروني)
- كل إشعار مرتبط بمستخدم وطلب محدد
- يحتوي على نوع الإشعار ونص عربي

### 12.2 أحداث الإشعار

| الحدث | نوع الإشعار | المرسل | المستقبل |
|-------|------------|--------|----------|
| العميل يُرسل طلب جديد | `NEW_ORDER` | النظام | جميع المديرين النشطين |
| المدير يعتمد طلب | `APPROVED` | المدير | صاحب الطلب |
| المدير يرفض طلب | `REJECTED` | المدير | صاحب الطلب (مع السبب) |
| المدير يُنفذ طلب | `EXECUTED` | المدير | صاحب الطلب |

### 12.3 واجهة الإشعارات
- **شارة غير المقروءين:** تظهر في الشريط العلوي، يتم تحديثها كل 30 ثانية عبر AJAX
- **صفحة الإشعارات:** `/notifications` — تظهر جميع الإشعارات مع إمكانية التحديد كمقروء
- **مسار AJAX:** `GET /notifications/unread-count` — يُعيد عدد غير المقروءين كـ JSON

---

## 13. نظم إدارة الوثائق

### 13.1 رفع الوثائق
- **الحد الأقصى:** 10 ميغابايت
- **الامتدادات المسموحة:** pdf, jpg, jpeg, png, docx
- **التحقق المزدوج:** فحص الامتداد + فحص نوع MIME
- **مكان التخزين:** `storage/app/private/documents/{order_id}/` (خارج المجلد العام)

### 13.2 قيود الرفع
- لا يمكن رفع وثائق على طلب بحالة `EXECUTED`, `CANCELLED`, أو `REJECTED`

### 13.3 تحميل الوثائق
- **المسار:** `/admin/orders/{order}/documents/{document}/download`
- **الصلاحية:** المدير فقط

### 13.4 أمان التخزين
- الوثائق محفوظة في `storage/app/private/` — أي أنها غير الوصول المباشر عبر الويب
- التحميل يتم عبر Controller مع التحقق من الصلاحيات

---

## 14. سجل التدقيق Audit Trail

### 14.1 العمليات المُسجّلة

| العملية | `action` | الوصف |
|---------|----------|-------|
| تسجيل دخول | `login` | عند نجاح تسجيل الدخول |
| إنشاء مستخدم | `create` | عند إنشاء مستخدم جديد |
| تعديل مستخدم | `update` | عند تعديل بيانات مستخدم |
| إعادة تعيين كلمة المرور | `reset_password` | عند إعادة التعيين |
| تفعيل/تعطيل حساب | `toggle_status` | عند تغيير حالة الحساب |
| تحديث الصلاحيات | `update` | عند تحديث صلاحيات الأدوار |

### 14.2 هيكل السجل

```sql
log_audit:
  user_id     → من نفّذ العملية
  action      → نوع العملية
  entity_type → نوع الكيان (users, categories, role_permissions)
  entity_id   → معرف الكيان
  notes       → ملاحظات إضافية
  created_at  → التوقيت
```

### 14.3 عرض السجل
- **تقرير المدير:** `/admin/reports/audit-trail` — يعرض الطلبات التي لها سجل اعتماد/رفض

---

## 15. واجهة المستخدم والتصميم

### 15.1 التخطيط الرئيسي
- **الملف:** `resources/views/layouts/app.blade.php`
- **الاتجاه:** RTL (من اليمين لليسار)
- **اللغة:** `lang="ar"`
- **الخط:** IBM Plex Sans Arabic (من Google Fonts)
- **ال.container:** `max-w-7xl` مع حشو مناسب

### 15.2 المكونات (Components)

| المكون | الملف | الوصف |
|--------|-------|-------|
| `<x-app-layout>` | `components/app-layout.blade.php` | التخطيط الرئيسي للصفحات |
| `<x-admin-nav>` | `components/admin-nav.blade.php` | شريط تنقل المدير (5 تبويبات) |
| `<x-investor-nav>` | `components/investor-nav.blade.php` | شريط تنقل المستثمر (6 تبويبات) |
| `<x-status-badge>` | `components/status-badge.blade.php` | شارة حالة الطلب (ملونة) |
| `<x-theme-toggle>` | `components/theme-toggle.blade.php` | زر تبديل السمة (فاتح/داكن) |

### 15.3 شارة الحالة

| الحالة | اللون | التسمية |
|--------|-------|---------|
| `DRAFT` | رمادي | مسودة |
| `PENDING` | أصفر/برتقالي | قيد الانتظار |
| `APPROVED` | أزرق | معتمد |
| `REJECTED` | أحمر | مرفوض |
| `EXECUTED` | أخضر | منفذ |
| `CANCELLED` | رمادي غامق | ملغي |

### 15.4 شريط تنقل المدير

| التبويب | الرابط |
|---------|--------|
| المستخدمون | `/admin/users` |
| الصلاحيات | `/admin/permissions` |
| التصنيفات | `/admin/categories` |
| الطلبات | `/admin/orders` |
| التقارير | `/admin/reports/dashboard` |

### 15.5 شريط تنقل المستثمر

| التبويب | الرابط |
|---------|--------|
| لوحة المعلومات | `/investor/dashboard` |
| الرصيد الحالي | `/investor/current-balance` |
| كشف الحركة | `/investor/movement-statement` |
| الإجماليات | `/investor/totals` |
| الصرف حسب التصنيف | `/investor/expenses-by-category` |
| الطلبات المعلقة | `/investor/pending-orders` |

### 15.6 عناصر واجهة المستخدم
- بطاقات مستديرة الزوايا (`rounded-xl`, `rounded-2xl`)
- أسطح بحدود (`border border-bdr bg-surface`)
- شريط علوي مثبت مع `backdrop-blur`
- تأثيرات hover سلسة
- صفحات طباعة مخصصة للتقارير

---

## 16. نظام الألوان والسمات Themes

### 16.1 الملف
**`resources/css/themes.css`**

### 16.2 الآلية
1. عنصر `<html>` يحتوي على `data-role="{role}"` و `data-theme="light|dark"`
2. مُعاملات CSS تُعرّف المتغيرات حسب الدور والسمة
3. Tailwind مُهيأ لاستخدام هذه المتغيرات
4. Alpine.js يتعامل مع التبديل ويحفظ في `localStorage`

### 16.3 الألوان حسب الدور

#### المدير (Admin)

| المتغير | الوضع الفاتح | الوضع الداكن |
|---------|-------------|-------------|
| `--bg` | `#F7F8FA` | `#0F1420` |
| `--surface` | `#FFFFFF` | `#1A2138` |
| `--primary` | `#1E2A4A` | `#3B82F6` |
| `--accent` | `#C2410C` | `#F59E0B` |
| `--text` | `#16213E` | `#E2E8F0` |
| `--border` | `#E2E5EA` | `#2A3550` |
| `--muted` | `#64748B` | `#8899AD` |

#### المستثمر (Investor)

| المتغير | الوضع الفاتح | الوضع الداكن |
|---------|-------------|-------------|
| `--bg` | `#F5F9F7` | `#0B1614` |
| `--surface` | `#FFFFFF` | `#142420` |
| `--primary` | `#0F5C4E` | `#2DD4BF` |
| `--accent` | `#D4AF37` | `#EAB308` |
| `--text` | `#1A2E29` | `#D1E7E2` |
| `--border` | `#D1E0DA` | `#1F3A32` |
| `--muted` | `#577A6B` | `#7A9E8E` |

#### العميل (Client)

| المتغير | الوضع الفاتح | الوضع الداكن |
|---------|-------------|-------------|
| `--bg` | `#F8FAFC` | `#101725` |
| `--surface` | `#FFFFFF` | `#1B2536` |
| `--primary` | `#2563EB` | `#60A5FA` |
| `--accent` | `#10B981` | `#34D399` |
| `--text` | `#1E293B` | `#E5EDF7` |
| `--border` | `#E2E8F0` | `#263244` |
| `--muted` | `#64748B` | `#8899AD` |

### 16.4 منع وميض السمة
يوجد سكريبت inline في `<head>` يقرأ `localStorage.theme` قبل تحميل الصفحة لمنع وميض تغيير السمة.

---

## 17. الطباعة وتوليد PDF

### 17.1 سندات العميل

| الوثيقة | المسار | النوع |
|---------|--------|-------|
| سند صرف | `/client/orders/{order}/disbursement-voucher` | PDF (DomPDF) |
| سند قبض | `/client/orders/{order}/receipt-voucher` | PDF (DomPDF) |

**الم_Controller:** `App\Http\Controllers\Client\OrderPdfController`

### 17.2 طباعة التقارير
- **11 مسار طباعة للمدير** — جميعها تُعيد صفحة HTML جاهزة للطباعة (بدون CSS مزخرف)
- **5 مسارات طباعة للمستثمر** — نفس النهج

### 17.3 تقرير الطلب
- **المسار:** `/admin/orders/{order}/report`
- **الوصف:** تقرير مفصل عن الطلب جاهز للطباعة

---

## 18. الأمان وأفضل الممارسات

### 18.1 أمان المصادقة
- كلمة المرور مشفرة بـ `bcrypt` (Hash::make)
- جلسات Laravel القياسية مع `remember_token`
- تسجيل خروج تلقائي عند تعطيل الحساب

### 18.2 أمان الصلاحيات
- middleware `role:admin/investor/client` على جميع المسارات المحمية
- فحص `is_active` في كل طلب
- حماية التعديل الذاتي (لا يمكن للمدير تعطيل حسابه)
- حماية آخر مدير نشط (لا يمكن تعطيل آخر مدير)
- لا يمكن لمنشئ الطلب اعتماده بنفسه

### 18.3 أمان الوثائق
- تخزين خارج المجلد العام (`storage/app/private/`)
- تحقق مزدوج من الامتداد ونوع MIME
- فحص الحجم الأقصى (10MB)
- لا يمكن الرفع على طلبات مكتملة/ملغاة/مرفوضة

### 18.4 أمان قاعدة البيانات
- معاملات قاعدة البيانات (`DB::transaction`) للعمليات المتعددة الخطوات
- `lockForUpdate()` لمنع سباق العمليات في حساب الرصيد
- `Restrict` على حذف الطلبات المرتبطة بالبنود وال Movements
- `Cascade` على حذف الإشعارات وسجل التدقيق عند حذف المستخدم

### 18.5 أمان الإدخال
- التحقق من صحة البيانات عبر `ValidationException`
- تشفير CSRF عبر `VerifyCsrfToken` middleware
- تجهيز المخرجات (Output Encoding) عبر Blade templates

### 18.6 سجل التدقيق
- تسجيل جميع العمليات الحساسة في `log_audit`
- تتبع: تسجيل الدخول، إنشاء/تعديل المستخدمين، إعادة تعيين كلمة المرور، تفعيل/تعطيل الحسابات، تحديث الصلاحيات

---

## 19. إخطارات الشبكة Routes API

### الملف: `routes/api.php`

```php
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

- **نقطة نهاية واحدة فقط:** `GET /api/user` — تُعيد بيانات المستخدم المصادق عليه
- **المصادقة:** Sanctum token-based (مثبت لكن لا يُستخدم بشكل نشط في التطبيق)
- التطبيق يعتمد بشكل أساسي على مسارات الويب (Web Routes) مع مصادقة الجلسات

---

## 20. الأوامر المتاحة artisan

### أوامر Laravel الأساسية
```bash
php artisan serve              # تشغيل خادم التطوير
php artisan migrate            # تشغيل الترحيلات
php artisan migrate:rollback   # التراجع عن آخر ترحيل
php artisan db:seed            # تشغيل البذور
php artisan key:generate       # توليد مفتاح التطبيق
php artisan cache:clear        # مسخ الكاش
php artisan config:clear       # مسخ الإعدادات
php artisan route:clear        # مسخ المسارات
php artisan view:clear         # مسخ العروض
php artisan optimize:clear     # مسخ جميع الكاش
```

### أوامر التطوير
```bash
npm run dev                    # تجميع الأصول للتطوير
npm run watch                  # مراقبة التغييرات
npm run prod                   # تجميع الأصول للإنتاج
```

---

## ملاحظات تقنية إضافية

### 1. استخدام bcmath
يُستخدم `bcmath` في ملفين رئيسيين:
- `OrderService::createDraft()` — للتحقق من تطابق مبالغ البنود مع الإجمالي
- `OrderService::execute()` — لحساب الرصيد الجديد

### 2. نظام الترقيم التلقائي
أرقام الطلبات تتبع الصيغة `ORD-YYYY-NNN` مع عداد فصلي يُعاد تعيينه كل سنة. العداد يستخدم `lockForUpdate()` لمنع التزامن.

### 3. نظام التحقق من التصنيفات
يتحقق النظام من أن التصنيف المختار يتوافق مع نوع الطلب:
- تصنيفات `payment` لا يمكن استخدامها في طلبات `receipt`
- تصنيفات `receipt` لا يمكن استخدامها في طلبات `payment`
- تصنيفات `both` يمكن استخدامها في النوعين

### 4. حماية آخر مدير نشط
يتحقق النظام من عدم تعطيل آخر مدير نشط في النظام لضمان عدم قفل الوصول.

### 5. التحقق من عدم التعديل الذاتي
- لا يمكن للمدير اعتماد طلبه الخاص
- لا يمكن للمدير تعطيل حسابه الخاص
- لا يمكن تغيير الدور للحساب الحالي

---

> **تم إنشاء هذا التوثيق بناءً على تحليل مباشر لملفات المصدر في المشروع.**
> **آخر تحديث:** يوليو 2026
