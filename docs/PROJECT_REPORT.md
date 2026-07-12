# تقرير محتويات المشروع — مولد تقارير ERP

> تقرير نهائي يوثّق بنية المشروع ومكوّناته وكيفية تشغيله ونتائج اختباره.
> تاريخ الإصدار: 2026-07-12

## 1. نظرة عامة

مولد تقارير احترافي لشركة ERP، مبني بـ **PHP 8.3** و **SQLite**، بواجهات **عربية RTL**.
يضم **لوحة تحكم** + **٣ تقارير جاهزة**: المبيعات، المخزون، المالية — مع فلاتر، رسوم بيانية (Chart.js)، وتصدير (CSV / Excel / طباعة PDF).

- **رابط المستودع:** https://github.com/alimossaed38/erp-report-generator
- **أُنشئ المستودع ورُفعت المهام أول بأول بواسطة Claude** (عبر `gh CLI` و `git`).

## 2. البنية والمكوّنات

```
erp-report-generator/
├── public/
│   └── index.php            ← Front Controller + توجيه + خدمة أصول /assets
├── app/
│   ├── Core/
│   │   ├── Router.php        ← تسجيل ومطابقة المسارات
│   │   ├── Database.php      ← اتصال PDO/SQLite (Singleton) + إنشاء مجلد DB
│   │   ├── Controller.php    ← الأساس: render(view,data) + json()
│   │   └── Request.php       ← قراءة الفلاتر (query params) بأمان
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── SalesController.php
│   │   ├── InventoryController.php
│   │   ├── FinanceController.php
│   │   └── ExportController.php
│   ├── Models/
│   │   ├── SalesRepository.php      ← summary / monthly / topProducts / invoices
│   │   ├── InventoryRepository.php  ← summary / products / valueByCategory / categories
│   │   └── FinanceRepository.php    ← summary / monthly / transactions
│   └── Views/
│       ├── layout.php        ← هيكل RTL: Sidebar + Header + أصول
│       ├── dashboard.php · sales.php · inventory.php · finance.php
├── database/
│   ├── schema.sql            ← تعريف الجداول (products, sales_invoices, sales_items, transactions)
│   └── seed.php              ← توليد بيانات تجريبية حتمية (١٢ شهر)
├── assets/
│   ├── css/app.css           ← نظام تصميم احترافي RTL + نمط طباعة
│   └── js/app.js             ← تفعيل Chart.js (سلسلة مفردة/متعددة)
├── tests/
│   ├── unit/                 ← 6 مجموعات اختبار (PHP)
│   ├── e2e/README.md         ← إجراء اختبار Playwright
│   ├── screenshots/          ← لقطات الواجهات
│   └── run_unit.php          ← مُشغّل اختبارات الوحدة
├── docs/                     ← الوثائق (spec, plan) + هذا التقرير
└── README.md
```

### طبقة البيانات (SQLite)
- `products(id, name, category, price, cost, stock_qty, reorder_level)`
- `sales_invoices(id, invoice_no, customer_name, invoice_date, total)`
- `sales_items(id, invoice_id, product_id, qty, unit_price, line_total)`
- `transactions(id, type[income|expense], category, amount, txn_date, description)`

البذور **حتمية** (مولّد LCG بذرة ثابتة) فتتكرر النتائج بدقة عبر التشغيلات. الحجم المولّد: **30 منتج، 223 فاتورة، 680 بند، 115 حركة مالية**، مع تطابق كامل بين إجمالي كل فاتورة ومجموع بنودها.

## 3. كيفية التشغيل

```bash
# 1) توليد قاعدة البيانات والبيانات التجريبية
"C:/wamp64/bin/php/php8.3.28/php.exe" database/seed.php

# 2) تشغيل الخادم (تمرير public/index.php ضروري لخدمة /assets)
"C:/wamp64/bin/php/php8.3.28/php.exe" -S 127.0.0.1:8010 -t public public/index.php

# 3) افتح المتصفح على
http://127.0.0.1:8010/
```

## 4. التقارير والميزات

| التقرير | المؤشرات (KPIs) | الرسم البياني | الفلاتر | التصدير |
|--------|------------------|---------------|---------|---------|
| لوحة التحكم | إجمالي المبيعات، صافي الربح، قيمة المخزون، أصناف ناقصة | مبيعات شهرية (خطي) + إيراد/مصروف (أعمدة) | — | — |
| المبيعات | إجمالي المبيعات، عدد الفواتير، متوسط الفاتورة | مبيعات شهرية (أعمدة) | من/إلى تاريخ | CSV · Excel · PDF |
| المخزون | إجمالي الأصناف، قيمة المخزون، أصناف ناقصة | قيمة المخزون حسب التصنيف | التصنيف | CSV · Excel · PDF |
| المالية | الإيرادات، المصروفات، صافي الربح | إيراد مقابل مصروف شهري | من/إلى تاريخ | CSV · Excel · PDF |

الأمان: كل الاستعلامات **مُعامَلة (parameterized)**، وكل مخرجات المستخدم عبر `htmlspecialchars`، وبيانات الرسوم عبر `JSON_UNESCAPED_UNICODE`، وممر الأصول محميّ من traversal.

## 5. نتائج الاختبار

### 5.1 اختبارات الوحدة (PHP)
`"C:/wamp64/bin/php/php8.3.28/php.exe" tests/run_unit.php`

```
== export_test.php ==      export_test OK
== finance_repo_test.php == finance_repo_test OK
== inventory_repo_test.php == inventory_repo_test OK
== router_test.php ==      router_test OK
== sales_repo_test.php ==  sales_repo_test OK
== seed_test.php ==        seed_test OK

ALL UNIT TESTS PASSED
```
**النتيجة: 6/6 مجموعات ناجحة.**

### 5.2 اختبار الواجهات (UI Automation — Playwright MCP)
قاد Claude متصفحاً فعلياً عبر أدوات Playwright وتحقّق من:

- **التنقّل:** الصفحات الأربع (`/`, `/sales`, `/inventory`, `/finance`) تُرجع 200 وعناوين عربية صحيحة، والأصول `/assets/*` تُخدَم (200)، والتصدير يُرجع ترويسة `Content-Disposition: attachment`.
- **العرض:** ظهور بطاقات KPI والجداول (بصفوف بيانات حقيقية) ورسوم Chart.js (canvases مرسومة فعلياً — تحقّق برمجي: مخطط المخزون يحمل 5 نقاط: 931,376 / 715,603 / 674,523 / 522,805 / 452,600).
- **فلتر المخزون:** اختيار التصنيف «إلكترونيات» ⇐ القائمة تعرض القيمة المختارة، الجدول يعرض **6 صفوف فقط كلها إلكترونيات**، ومؤشر «إجمالي الأصناف» ينخفض من **30 إلى 6**.
- **فلتر المبيعات:** المدى `2026-01-01 → 2026-03-31` ⇐ «عدد الفواتير» ينخفض من **223 إلى 58**، وكل تواريخ الفواتير المعروضة **داخل المدى** (صفر خارج النطاق).
- **الكونسول:** خالٍ من الأخطاء (بعد إضافة أيقونة مضمّنة).

## 6. لقطات الواجهات

### لوحة التحكم
![لوحة التحكم](../tests/screenshots/dashboard.png)

### تقرير المبيعات
![المبيعات](../tests/screenshots/sales.png)

### تقرير المخزون
![المخزون](../tests/screenshots/inventory.png)

### التقرير المالي
![المالية](../tests/screenshots/finance.png)

## 7. سجل الرفع إلى GitHub (أول بأول)

رُفعت كل مرحلة كـ commit مستقل عبر Claude:

```
ba102a4 test(e2e): add UI screenshots + inline favicon
7f675a5 test: unit runner + Playwright E2E procedure doc
5bf6730 feat(export): CSV + Excel export for all reports
f9025ff feat(finance,dashboard): repositories, controllers, views
b19fcf8 docs: require router-script form in serve command so /assets are served
a3f1050 feat(ui): design system + layout + inventory report
f86ab98 feat(sales): repository, controller, view
3cf8d74 feat(db): schema + deterministic seed data
d976855 fix(core): ensure database/ directory exists in Database::connection
bce8c8d feat(core): router, database, controller, request, front controller
c66e819 chore: scaffold project + smoke page + README
1575fe1 docs: add implementation plan
f289354 docs: add ERP report generator design spec
```

## 8. الخلاصة

مشروع مكتمل يحقّق كل الشروط المطلوبة:
1. ✅ **إنشاء المستودع على GitHub بواسطة Claude.**
2. ✅ **رفع المهام أول بأول** (13 commit مرحلي).
3. ✅ **اختبار يدوي وآلي** بأدوات UI Automation (Playwright) + اختبارات وحدة.
4. ✅ **تقرير محتويات المشروع** (هذا المستند).

المشروع مبني بمعمارية MVC نظيفة، قابل للتوسّع (إضافة تقارير جديدة = Repository + Controller + View)، وجاهز للعرض كنموذج احترافي.
