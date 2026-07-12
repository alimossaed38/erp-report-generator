# ترقية التقارير الاحترافية — وثيقة التصميم (Spec)

**التاريخ:** 2026-07-12
**الحالة:** معتمد
**الأساس:** v2.0 "Ali ERP Analytics" (commit `51d82bf`)

## 1. الهدف والنطاق

رفع تقارير النظام إلى مستوى احترافي تعتمد عليه الإدارة، بإضافة أربع حزم:
1. **تحليلات أعمق + أهداف** — نمو MoM/YoY، متوسط متحرك، هامش الربح، أهداف مقابل الفعلي.
2. **التعمق (Drill-down)** — صفحات تفاصيل العميل/المنتج/الفاتورة + تقرير العملاء.
3. **PDF احترافي بهوية** — تخطيط طباعة رسمي بشعار وترويسة/تذييل (طباعة المتصفح → PDF).
4. **تقارير ERP إضافية** — أعمار الذمم (AR Aging) + ملخص تنفيذي (Executive Summary).

**خارج النطاق (YAGNI):** توليد PDF عبر مكتبة خادم، إرسال بريد/جدولة، مصادقة/مستخدمون، تعدد عملات، بناء تقارير ديناميكي (Report Builder).

## 2. المقاربة والقيود

- يُبنى فوق v2.0 مع الحفاظ على أنماطه: مستودعات بمعاملات مسماة و`Report::pagination`، ومساعدات `Ui`/`Config`/`Report`، وموجّه تطابق تام (المسارات الفرعية بمعاملات query).
- بدون Composer/مكتبات خادم. الرسوم بـ JS المحلي القائم. العربية RTL و`Ui::e`/معاملات SQL في كل مكان.
- تشغيل: `"C:/wamp64/bin/php/php8.3.28/php.exe" -S 127.0.0.1:8010 -t public public/index.php`.
- البذور تبقى **حتمية** (LCG بذرة ثابتة).
- تاريخ "كما في" (as-of) للأعمار = أحدث `invoice_date` في البيانات (`SalesRepository::dateBounds()['max']`).

## 3. تعديلات نموذج البيانات (schema.sql + seed.php)

### 3.1 `sales_invoices` — أعمدة جديدة
```sql
ALTER … -- عبر إعادة إنشاء الجدول في schema.sql:
sales_invoices( id, invoice_no, customer_name, invoice_date, total,
                due_date TEXT NOT NULL,        -- = invoice_date + net terms (30 يوم)
                amount_paid REAL NOT NULL DEFAULT 0 )
```
حالة السداد تُشتق: `paid` إذا amount_paid ≥ total، `partial` إذا 0<paid<total، `unpaid` إذا paid=0.
`outstanding = max(0, total - amount_paid)`.

### 3.2 جدول `targets` جديد
```sql
CREATE TABLE targets (
    period TEXT PRIMARY KEY,      -- 'YYYY-MM'
    sales_target REAL NOT NULL
);
```
بذور: هدف شهري لكل من الأشهر الـ12، ≈ فعلي الشهر × عامل حتمي (0.85–1.15) ليكون الإنجاز واقعياً (بعضه فوق/تحت الهدف).

### 3.3 بذور السداد (حتمية)
لكل فاتورة: توزيع حتمي — ~65% مدفوعة كاملة، ~15% جزئية (paid = total×نسبة)، ~20% غير مدفوعة. `due_date = invoice_date + 30 يوم`. هذا يولّد ذمماً مستحقة وبعضها متأخر نسبة إلى as-of.

### 3.4 فهارس
`CREATE INDEX idx_sales_invoices_due ON sales_invoices(due_date);`

## 4. طبقة التحليلات — `app/Core/Analytics.php` (جديد، static)

```php
Analytics::growth(array $monthly, string $valueKey): array
  // يُرجع لكل عنصر: + mom (٪ عن الشهر السابق) و yoy (٪ عن نفس الشهر قبل 12) — null إن غير متاح.

Analytics::movingAverage(array $monthly, string $valueKey, int $window = 3): array
  // يُرجع نفس القائمة مع مفتاح 'ma' = متوسط النافذة (أو null قبل اكتمالها).

Analytics::agingBuckets(array $invoices, string $asOf): array
  // من فواتير فيها outstanding+due_date → دلاء:
  // ['current'=>x,'d1_30'=>x,'d31_60'=>x,'d61_90'=>x,'d90_plus'=>x,'total'=>x] (مبالغ)
  // + 'counts' لكل دلو. الجاري = due_date >= asOf؛ الباقي حسب أيام التأخر.

Analytics::targetProgress(float $actual, ?float $target): array
  // ['actual','target','pct'=>?float,'remaining'=>?float,'met'=>bool]
```
لا منطق أعمال في الـ Views؛ الحسابات هنا أو في المستودعات.

## 5. توسيع المستودعات

- **SalesRepository:** إضافة أعمدة السداد لصفوف الفواتير؛ `marginSummary(?from,?to,?search): ['revenue','cogs','profit','margin_pct']` (COGS = Σ qty×products.cost)؛ `monthlyMargin(?from,?to): [{ym, revenue, cogs, profit}]`؛ `outstanding(?from,?to): ['total_outstanding','overdue','invoice_count']`؛ `agingInvoices(): صفوف فيها outstanding>0 مع due_date` لتقرير الأعمار؛ `customerReport(?from,?to,?search,page,perPage,sort,dir)`؛ `customerDetail(name)` + `customerInvoices(name)` + `customerMonthly(name)`؛ `invoiceByNo(no)` + `invoiceItems(invoiceId)`.
- **InventoryRepository:** `productByIdWithStats(id): [منتج + إيراد وكمية مباعة وهامش]`؛ `productSalesMonthly(id)`؛ `productInvoices(id)`.
- **FinanceRepository:** `monthlyMargin`-نمط مُعاد استخدامه للأهداف/الهامش إن لزم (وإلا يكفي منطق Sales).
- **جديد `TargetRepository`:** `all(): [period=>sales_target]`؛ `forPeriod(period): ?float`؛ `range(from,to): float` (مجموع أهداف الأشهر في المدى).

## 6. ترقية الصفحات الحالية

- **لوحة المعلومات:** بطاقات جديدة/محسّنة: نمو المبيعات (MoM/YoY)، **المستحقات (AR)** مع عدد المتأخرة، **الهدف مقابل الفعلي** للشهر الحالي (شريط تقدّم %)، **هامش الربح**؛ إضافة خط **متوسط متحرك** على منحنى المبيعات؛ شريط **ملخص تنفيذي** مختصر مع رابط `/summary`.
- **المبيعات:** مؤشرات **الربح والهامش** للفترة، **نمو الفترة** مقابل السابقة (موجود جزئياً)، **تقدّم الهدف** للمدى، وتحويل أسماء العملاء في الجدول إلى روابط `/customers/view?name=`.
- **المخزون:** عمود/مؤشر **هامش الربح** لكل صنف (متاح `potential_profit`)، واسم المنتج رابط `/products/view?id=`.
- **المالية:** إضافة **اتجاه هامش الربح الشهري** (رسم) و**تقدّم الهدف** (اختياري ضمن نفس الصفحة).

## 7. الصفحات الجديدة (مسارات + تحكم + عرض)

| المسار | المتحكم | الوصف |
|--------|---------|-------|
| `/customers` | CustomersController::index | تقرير العملاء: إيراد، عدد فواتير، متوسط، آخر شراء، **رصيد مستحق**؛ بحث/ترتيب/صفحات |
| `/customers/view` (`?name=`) | CustomersController::view | تفاصيل عميل: مؤشرات + كل فواتيره + اتجاه شهري + مستحقاته |
| `/products/view` (`?id=`) | ProductController::view | تفاصيل منتج: الحالة، تاريخ المبيعات (رسم)، الهامش، الفواتير المتضمّنة |
| `/invoices/view` (`?no=`) | InvoiceController::view | تفاصيل فاتورة: الترويسة + البنود + الإجماليات + حالة السداد |
| `/aging` | AgingController::index | تقرير أعمار الذمم: ملخص الدلاء (رسم + بطاقات) + جدول الفواتير المستحقة وأيام التأخير |
| `/summary` | SummaryController::index | ملخص تنفيذي: نظرة صفحة واحدة عبر كل المجالات (هدف الطباعة) |

- روابط التنقّل الجانبي تُوسَّع لتشمل: العملاء، أعمار الذمم، الملخص التنفيذي (بأيقونات `users`/`receipt`/`trend` الموجودة).
- الصفحات غير الموجودة (اسم عميل/منتج/فاتورة غير صحيح) تعرض حالة فارغة ودّية (لا خطأ 500).

## 8. الطباعة/PDF بهوية

- `config/app.php`: إضافة `company_logo` (SVG مضمّن/رمز)، `company_tagline`، `report_footer`، `net_terms_days` (30)، ودلاء الأعمار.
- `assets/css/app.css`: كتلة `@media print` موسّعة + `@page { size: A4; margin }` مع ترقيم صفحات (`@bottom-center`/counters عبر عنصر تذييل ثابت)، إظهار **ترويسة طباعة** (شعار + اسم الشركة + عنوان التقرير + الفترة + تاريخ الإصدار) وتذييل، وإخفاء التنقّل/الأزرار، وتحويل الألوان لطباعة نظيفة (أبيض/أسود مع حدود).
- كتلة ترويسة/تذييل الطباعة تُضاف في `layout.php` (تظهر فقط عند الطباعة) وتقرأ من `Config`.
- زر «طباعة / PDF» موجود في كل تقرير؛ يُضاف لصفحات التعمق والملخص.

## 9. الاختبار

- **وحدات (PHP، حتمية):** `analytics_test` (growth/movingAverage/agingBuckets/targetProgress بقيم معروفة)، `targets_test` (جدول الأهداف + مجموع المدى)، توسيع `seed_test` (وجود due_date/amount_paid/targets وسلامة outstanding)، `customers_test`/`aging_test` (تجميعات المستودع)، وتحديث الاختبارات المتأثرة. الهدف: كل المجموعات خضراء عبر `tests/run_unit.php`.
- **آلي (Playwright، يقوده Claude):** فتح كل صفحة جديدة والتحقق من المؤشرات/الجداول/الرسوم؛ اختبار روابط التعمق (عميل→تفاصيله، منتج→تفاصيله، فاتورة→بنودها)؛ اختبار تقرير الأعمار (مجموع الدلاء = إجمالي المستحق)؛ اختبار تقدّم الهدف؛ ولقطات محدّثة.

## 10. التقسيم المرحلي (كل مرحلة: TDD + commit + push)

- **A — الأساس:** schema (due_date/amount_paid/targets) + seed + `TargetRepository` + اختبارات.
- **B — التحليلات:** `Analytics` (growth/MA/aging/targets) + توسيع مستودعات الهامش/المستحقات + اختبارات.
- **C — ترقية المبيعات + اللوحة:** هامش/نمو/هدف/AR + متوسط متحرك + روابط العملاء.
- **D — ترقية المخزون + المالية:** هامش المنتج + روابط المنتج + اتجاه هامش المالية.
- **E — التعمق:** تقرير العملاء + تفاصيل العميل/المنتج/الفاتورة.
- **F — أعمار الذمم:** `/aging` كامل.
- **G — الملخص التنفيذي:** `/summary` كامل.
- **H — طباعة/PDF بهوية:** ترويسة/تذييل + `@page` + `config` + تطبيقها على كل التقارير.

## 11. معايير النجاح

- كل الصفحات (القديمة + الجديدة) تعمل 200 بلا أخطاء console/PHP.
- التحليلات صحيحة (نمو/هامش/أهداف/أعمار) ومغطّاة باختبارات وحدة حتمية خضراء.
- التعمق يعمل من كل نقطة دخول، والحالات الفارغة ودّية.
- تقرير الأعمار: مجموع الدلاء = إجمالي المستحق.
- الطباعة تُنتج PDF بهوية رسمية (ترويسة/تذييل/ترقيم).
- كل مرحلة مرفوعة أول بأول إلى GitHub، والمراجعة النهائية نظيفة.
