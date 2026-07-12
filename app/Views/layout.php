<?php /** @var string $__view */ $active = $active ?? ''; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'تقارير ERP') ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><text y='14' font-size='14'>📊</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<aside class="sidebar">
    <div class="brand">تقارير ERP</div>
    <nav>
        <a href="/" class="<?= $active==='dashboard'?'on':'' ?>">لوحة التحكم</a>
        <a href="/sales" class="<?= $active==='sales'?'on':'' ?>">المبيعات</a>
        <a href="/inventory" class="<?= $active==='inventory'?'on':'' ?>">المخزون</a>
        <a href="/finance" class="<?= $active==='finance'?'on':'' ?>">المالية</a>
    </nav>
    <div class="sidebar-foot">SmartERP • 2026</div>
</aside>
<div class="main">
    <header class="topbar"><h1 class="topbar-title"><?= htmlspecialchars($title ?? '') ?></h1></header>
    <main class="content">
        <?php require $__view; ?>
    </main>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
