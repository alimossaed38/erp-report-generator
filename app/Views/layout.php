<?php
/** @var string $__view */
$active = $active ?? '';
$appName = (string) Config::get('name', 'ERP Analytics');
$company = (string) Config::get('company', 'الشركة');
$navItems = [
    'dashboard' => ['href' => '/', 'label' => 'لوحة المعلومات', 'icon' => 'dashboard'],
    'summary' => ['href' => '/summary', 'label' => 'الملخص التنفيذي', 'icon' => 'trend'],
    'sales' => ['href' => '/sales', 'label' => 'المبيعات', 'icon' => 'sales'],
    'customers' => ['href' => '/customers', 'label' => 'العملاء', 'icon' => 'users'],
    'aging' => ['href' => '/aging', 'label' => 'أعمار الذمم', 'icon' => 'receipt'],
    'inventory' => ['href' => '/inventory', 'label' => 'المخزون', 'icon' => 'inventory'],
    'finance' => ['href' => '/finance', 'label' => 'المالية', 'icon' => 'finance'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2747">
    <title><?= Ui::e($title ?? 'تقارير ERP') ?> — <?= Ui::e($appName) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='16' fill='%230f2747'/><path d='M17 42V30h8v12m7 0V20h8v22m7 0V26h8v16' stroke='%235eead4' stroke-width='5' stroke-linecap='round'/></svg>">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="القائمة الرئيسية">
        <div class="brand-block">
            <div class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></div>
            <div>
                <strong><?= Ui::e((string) Config::get('short_name', 'ERP')) ?></strong>
                <small>Business Intelligence</small>
            </div>
        </div>

        <div class="sidebar-section-label">التقارير الرئيسية</div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $key => $item): ?>
                <a href="<?= Ui::e($item['href']) ?>" class="nav-item <?= $active === $key ? 'is-active' : '' ?>">
                    <?= Ui::icon($item['icon']) ?>
                    <span><?= Ui::e($item['label']) ?></span>
                    <?php if ($active === $key): ?><i aria-hidden="true"></i><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-insight">
            <div class="sidebar-insight-icon"><?= Ui::icon('trend', 19) ?></div>
            <strong>مركز تحليلات موحّد</strong>
            <p>تصفية، مقارنة، طباعة وتصدير البيانات من مكان واحد.</p>
        </div>

        <div class="sidebar-footer">
            <div class="company-avatar"><?= Ui::e(Ui::slice($company, 1)) ?></div>
            <div><strong><?= Ui::e($company) ?></strong><small>بيانات تجريبية</small></div>
        </div>
    </aside>
    <button class="sidebar-overlay" type="button" data-sidebar-close aria-label="إغلاق القائمة"></button>

    <div class="main-area">
        <header class="topbar">
            <div class="topbar-start">
                <button type="button" class="icon-button mobile-menu" data-sidebar-toggle aria-label="فتح القائمة">
                    <?= Ui::icon('menu') ?>
                </button>
                <div class="page-heading-compact">
                    <span>التقارير</span>
                    <strong><?= Ui::e($title ?? '') ?></strong>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="data-status"><span></span> قاعدة البيانات متصلة</div>
                <button type="button" class="icon-button" data-theme-toggle aria-label="تبديل المظهر" title="تبديل المظهر">
                    <span class="theme-icon-moon"><?= Ui::icon('moon') ?></span>
                    <span class="theme-icon-sun"><?= Ui::icon('sun') ?></span>
                </button>
            </div>
        </header>

        <main class="page-content">
            <section class="page-hero">
                <div>
                    <div class="eyebrow"><span></span> مركز التقارير والتحليلات</div>
                    <h1><?= Ui::e($title ?? '') ?></h1>
                    <?php if (!empty($subtitle)): ?><p><?= Ui::e($subtitle) ?></p><?php endif; ?>
                </div>
                <div class="hero-date">
                    <?= Ui::icon('calendar', 18) ?>
                    <div><small>آخر تحديث</small><strong><?= Ui::e(date('Y-m-d · H:i')) ?></strong></div>
                </div>
            </section>

            <?php require $__view; ?>
        </main>

        <footer class="page-footer">
            <span><?= Ui::e($appName) ?> · <?= date('Y') ?></span>
            <span>نظام تقارير داخلي</span>
        </footer>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
