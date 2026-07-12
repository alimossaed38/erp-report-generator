# مولد تقارير ERP

مولد تقارير احترافي (PHP 8.3 + SQLite) بواجهات عربية RTL: المبيعات، المخزون، المالية + لوحة تحكم.

## التشغيل
```bash
# 1) توليد قاعدة البيانات والبيانات التجريبية
"C:/wamp64/bin/php/php8.3.28/php.exe" database/seed.php
# 2) تشغيل الخادم
"C:/wamp64/bin/php/php8.3.28/php.exe" -S 127.0.0.1:8010 -t public
# 3) افتح
http://127.0.0.1:8010/
```

## البنية
- `public/` جذر الخادم — `index.php` (Front Controller)
- `app/Core` — Router, Database, Controller
- `app/Controllers`, `app/Models`, `app/Views`
- `database/` — schema + seed
- `tests/e2e` — اختبارات Playwright
- `docs/` — الوثائق وتقرير المشروع
