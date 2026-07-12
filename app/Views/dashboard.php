<?php
/** @var array $salesSummary @var array $invSummary @var array $finSummary */
$topRevenue = !empty($topProducts) ? max(array_column($topProducts, 'revenue')) : 1;
$lowRatio = $invSummary['items'] > 0 ? ($invSummary['low'] / $invSummary['items']) * 100 : 0;
$lastMonth = $salesMonthly ? end($salesMonthly) : null;
$momValue = $lastMonth['mom'] ?? null;
$targetPctClamped = $target['pct'] !== null ? min(100, max(0, $target['pct'])) : 0;
?>
<section class="metric-grid metric-grid-4">
    <article class="metric-card accent-blue">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('sales') ?></span><span class="metric-trend <?= ($salesTrend ?? 0) >= 0 ? 'up' : 'down' ?>"><?= ($salesTrend ?? 0) >= 0 ? Ui::icon('arrow-up', 14) : Ui::icon('arrow-down', 14) ?> <?= Ui::percent($salesTrend) ?></span></div>
        <span class="metric-label">إجمالي المبيعات</span>
        <strong><?= Ui::money($salesSummary['total']) ?></strong>
        <small><?= Ui::number($salesSummary['count']) ?> فاتورة · <?= Ui::number($salesSummary['customers']) ?> عميل</small>
    </article>
    <article class="metric-card accent-green">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('finance') ?></span><span class="metric-trend <?= ($financeTrend ?? 0) >= 0 ? 'up' : 'down' ?>"><?= ($financeTrend ?? 0) >= 0 ? Ui::icon('arrow-up', 14) : Ui::icon('arrow-down', 14) ?> <?= Ui::percent($financeTrend) ?></span></div>
        <span class="metric-label">صافي التدفق المالي</span>
        <strong><?= Ui::money($finSummary['net']) ?></strong>
        <small>هامش صافي <?= Ui::percent($finSummary['margin']) ?></small>
    </article>
    <article class="metric-card accent-violet">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('box') ?></span><span class="metric-mini">مخزون حالي</span></div>
        <span class="metric-label">قيمة المخزون</span>
        <strong><?= Ui::money($invSummary['value']) ?></strong>
        <small><?= Ui::number($invSummary['units']) ?> وحدة عبر <?= Ui::number($invSummary['items']) ?> صنف</small>
    </article>
    <article class="metric-card accent-orange">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('warning') ?></span><span class="metric-mini"><?= Ui::number($lowRatio, 1) ?>% من الأصناف</span></div>
        <span class="metric-label">أصناف تحتاج انتباه</span>
        <strong><?= Ui::number($invSummary['low']) ?></strong>
        <small><?= Ui::number($invSummary['out_of_stock']) ?> صنف نفد بالكامل</small>
    </article>
</section>

<section class="metric-grid metric-grid-4">
    <article class="metric-card accent-blue">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('trend') ?></span></div>
        <span class="metric-label">نمو المبيعات (شهري)</span>
        <strong><?= Ui::percent($momValue) ?></strong>
        <small>مقارنة بالشهر السابق</small>
    </article>
    <article class="metric-card accent-orange">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('receipt') ?></span></div>
        <span class="metric-label">المستحقات (الذمم المدينة)</span>
        <strong><?= Ui::money($outstanding['total_outstanding']) ?></strong>
        <small>متأخر السداد: <?= Ui::money($outstanding['overdue']) ?></small>
    </article>
    <article class="metric-card accent-violet">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('target') ?></span></div>
        <span class="metric-label">الهدف مقابل الفعلي (<?= Ui::e($currentYm) ?>)</span>
        <div class="progress<?= ($target['pct'] !== null && $target['pct'] < 60) ? ' danger' : '' ?>"><i style="width:<?= $targetPctClamped ?>%"></i></div>
        <small><?= Ui::money($target['actual']) ?> من <?= $target['target'] !== null ? Ui::money($target['target']) : '—' ?> (<?= Ui::percent($target['pct']) ?>)</small>
    </article>
    <article class="metric-card accent-green">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('finance') ?></span></div>
        <span class="metric-label">هامش الربح</span>
        <strong><?= Ui::percent($margin['margin_pct']) ?></strong>
        <small>صافي الربح: <?= Ui::money($margin['profit']) ?></small>
    </article>
</section>

<section class="panel exec-summary">
    <div class="panel-head">
        <div><span class="panel-kicker">ملخص تنفيذي</span><h2>أداء الشركة في لمحة</h2></div>
        <a class="text-link" href="/summary">التقرير التنفيذي الكامل <?= Ui::icon('chevron-left', 15) ?></a>
    </div>
    <p class="exec-summary-text">
        حقّقت المبيعات <?= Ui::money($salesSummary['total']) ?> بنمو شهري <?= Ui::percent($momValue) ?>،
        بهامش ربح <?= Ui::percent($margin['margin_pct']) ?>، بينما تبلغ المستحقات القائمة <?= Ui::money($outstanding['total_outstanding']) ?>
        وتحقق الهدف الشهري بنسبة <?= Ui::percent($target['pct']) ?>.
    </p>
</section>

<section class="dashboard-grid">
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div><span class="panel-kicker">اتجاه الإيرادات</span><h2>المبيعات الشهرية</h2></div>
            <a class="text-link" href="/sales">عرض تقرير المبيعات <?= Ui::icon('chevron-left', 15) ?></a>
        </div>
        <div class="chart-box chart-lg">
            <div class="chart-legend"><span><i style="background:var(--primary)"></i> المبيعات</span><span><i style="background:var(--orange)"></i> متوسط متحرك (3 أشهر)</span></div>
            <canvas data-chart="line" data-label="المبيعات" data-values='<?= Ui::e(json_encode(array_map(static fn($m) => ['label' => $m['ym'], 'series' => ['المبيعات' => $m['total'], 'متوسط متحرك' => $m['ma']]], $salesMonthly), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>'></canvas>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">الأكثر تأثيرًا</span><h2>أفضل المنتجات</h2></div></div>
        <div class="rank-list">
            <?php foreach ($topProducts as $index => $product): ?>
                <div class="rank-row">
                    <span class="rank-no"><?= $index + 1 ?></span>
                    <div class="rank-content"><div><strong><?= Ui::e($product['name']) ?></strong><span><?= Ui::number($product['qty']) ?> وحدة</span></div><div class="progress"><i style="width:<?= max(5, ($product['revenue'] / $topRevenue) * 100) ?>%"></i></div></div>
                    <b><?= Ui::money($product['revenue']) ?></b>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel panel-span-2">
        <div class="panel-head"><div><span class="panel-kicker">التدفق النقدي</span><h2>الإيرادات والمصروفات</h2></div><a class="text-link" href="/finance">التفاصيل <?= Ui::icon('chevron-left', 15) ?></a></div>
        <div class="chart-box"><canvas data-chart="grouped" data-values='<?= Ui::e(json_encode(array_map(static fn($m) => ['label' => $m['ym'], 'series' => $m['series']], $finMonthly), JSON_UNESCAPED_UNICODE)) ?>'></canvas></div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">تنبيهات مباشرة</span><h2>المخزون المنخفض</h2></div><a class="text-link" href="/inventory?status=low">عرض الكل</a></div>
        <?php if ($lowStock): ?>
            <div class="alert-list">
                <?php foreach ($lowStock as $product): ?>
                    <div class="alert-row"><span class="status-dot <?= (int) $product['stock_qty'] === 0 ? 'danger' : 'warning' ?>"></span><div><strong><?= Ui::e($product['name']) ?></strong><small><?= Ui::e($product['category']) ?></small></div><b><?= (int) $product['stock_qty'] ?> / <?= (int) $product['reorder_level'] ?></b></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?><div class="empty-state compact">كل مستويات المخزون آمنة حاليًا.</div><?php endif; ?>
    </article>
</section>

<section class="panel">
    <div class="panel-head"><div><span class="panel-kicker">آخر النشاطات</span><h2>أحدث الفواتير</h2></div><span class="date-range-note"><?= Ui::e($salesBounds['min'] ?? '') ?> — <?= Ui::e($salesBounds['max'] ?? '') ?></span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>التاريخ</th><th>الإجمالي</th><th>الحالة</th></tr></thead>
            <tbody>
            <?php foreach ($recentInvoices as $invoice): ?>
                <tr><td><span class="code-cell"><?= Ui::e($invoice['invoice_no']) ?></span></td><td><strong><?= Ui::e($invoice['customer_name']) ?></strong></td><td><?= Ui::e($invoice['invoice_date']) ?></td><td class="money-cell"><?= Ui::money($invoice['total']) ?></td><td><span class="badge success"><i></i> مكتملة</span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
