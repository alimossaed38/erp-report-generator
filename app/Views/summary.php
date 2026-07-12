<?php
/** @var array $salesSummary @var ?float $salesMomGrowth @var array $margin @var array $monthlyMargin
 * @var array $outstanding @var array $agingBuckets @var array $bucketLabels @var ?string $asOf
 * @var array $targetCurrent @var array $targetRange @var string $currentYm
 * @var array $invSummary @var array $finSummary @var array $topCustomers @var array $topProducts @var array $bounds
 */
$bucketOrder = ['current', 'd1_30', 'd31_60', 'd61_90', 'd90_plus'];
$bucketAccents = [
    'current' => 'accent-green',
    'd1_30' => 'accent-blue',
    'd31_60' => 'accent-orange',
    'd61_90' => 'accent-red',
    'd90_plus' => 'accent-violet',
];
$topCustomerMax = !empty($topCustomers) ? max(array_column($topCustomers, 'revenue')) : 1;
$topProductMax = !empty($topProducts) ? max(array_column($topProducts, 'revenue')) : 1;
$trendChart = array_map(static fn(array $m): array => [
    'label' => $m['ym'],
    'series' => ['الإيرادات' => $m['revenue'], 'الربح' => $m['profit']],
], $monthlyMargin);
?>
<div class="report-toolbar">
    <div class="result-summary"><strong><?= Ui::e($bounds['min'] ?? '') ?></strong> — <strong><?= Ui::e($bounds['max'] ?? '') ?></strong><span>نطاق البيانات الكامل</span></div>
    <div class="export-actions">
        <button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button>
    </div>
</div>

<section class="metric-grid metric-grid-6">
    <article class="metric-card accent-blue">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('sales') ?></span><span class="metric-trend <?= ($salesMomGrowth ?? 0) >= 0 ? 'up' : 'down' ?>"><?= ($salesMomGrowth ?? 0) >= 0 ? Ui::icon('arrow-up', 14) : Ui::icon('arrow-down', 14) ?> <?= Ui::percent($salesMomGrowth) ?></span></div>
        <span class="metric-label">إجمالي المبيعات</span>
        <strong><?= Ui::money($salesSummary['total']) ?></strong>
        <small><?= Ui::number($salesSummary['count']) ?> فاتورة · <?= Ui::number($salesSummary['customers']) ?> عميل</small>
    </article>
    <article class="metric-card accent-green">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('finance') ?></span><span class="metric-mini">صافي الربح</span></div>
        <span class="metric-label">الربح</span>
        <strong><?= Ui::money($margin['profit']) ?></strong>
        <small>الإيرادات - تكلفة البضاعة المباعة</small>
    </article>
    <article class="metric-card accent-violet">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('target') ?></span><span class="metric-mini">هامش الربح</span></div>
        <span class="metric-label">نسبة الهامش</span>
        <strong><?= Ui::percent($margin['margin_pct']) ?></strong>
        <small>الربح ÷ الإيرادات</small>
    </article>
    <article class="metric-card accent-orange">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('finance') ?></span><span class="metric-mini">هامش صافي <?= Ui::percent($finSummary['margin']) ?></span></div>
        <span class="metric-label">صافي التدفق النقدي</span>
        <strong><?= Ui::money($finSummary['net']) ?></strong>
        <small>إيرادات <?= Ui::money($finSummary['income']) ?> · مصروفات <?= Ui::money($finSummary['expense']) ?></small>
    </article>
    <article class="metric-card accent-red">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('receipt') ?></span><span class="metric-mini"><?= Ui::number($outstanding['invoice_count']) ?> فاتورة</span></div>
        <span class="metric-label">المستحقات (الذمم المدينة)</span>
        <strong><?= Ui::money($outstanding['total_outstanding']) ?></strong>
        <small>متأخر السداد: <?= Ui::money($outstanding['overdue']) ?></small>
    </article>
    <article class="metric-card accent-blue">
        <div class="metric-top"><span class="metric-icon"><?= Ui::icon('box') ?></span><span class="metric-mini"><?= Ui::number($invSummary['low']) ?> منخفض / <?= Ui::number($invSummary['out_of_stock']) ?> نافد</span></div>
        <span class="metric-label">قيمة المخزون</span>
        <strong><?= Ui::money($invSummary['value']) ?></strong>
        <small><?= Ui::number($invSummary['units']) ?> وحدة عبر <?= Ui::number($invSummary['items']) ?> صنف</small>
    </article>
</section>

<section class="two-column-grid">
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">الأداء مقابل الخطة</span><h2>هدف الشهر الحالي (<?= Ui::e($currentYm) ?>)</h2></div></div>
        <div class="target-progress">
            <div class="target-progress-head"><span>نسبة الإنجاز</span><strong><?= Ui::percent($targetCurrent['pct']) ?></strong></div>
            <div class="progress<?= ($targetCurrent['pct'] !== null && $targetCurrent['pct'] < 60) ? ' danger' : '' ?>"><i style="width:<?= $targetCurrent['pct'] !== null ? min(100, max(0, $targetCurrent['pct'])) : 0 ?>%"></i></div>
            <div class="target-progress-foot"><span>الفعلي: <?= Ui::money($targetCurrent['actual']) ?></span><span>الهدف: <?= $targetCurrent['target'] !== null ? Ui::money($targetCurrent['target']) : '—' ?></span></div>
        </div>
    </article>
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">الأداء مقابل الخطة</span><h2>هدف كامل الفترة</h2></div></div>
        <div class="target-progress">
            <div class="target-progress-head"><span>نسبة الإنجاز</span><strong><?= Ui::percent($targetRange['pct']) ?></strong></div>
            <div class="progress<?= ($targetRange['pct'] !== null && $targetRange['pct'] < 60) ? ' danger' : '' ?>"><i style="width:<?= $targetRange['pct'] !== null ? min(100, max(0, $targetRange['pct'])) : 0 ?>%"></i></div>
            <div class="target-progress-foot"><span>الفعلي: <?= Ui::money($targetRange['actual']) ?></span><span>الهدف: <?= $targetRange['target'] !== null ? Ui::money($targetRange['target']) : '—' ?></span></div>
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-head">
        <div><span class="panel-kicker">اتجاه الأداء</span><h2>المبيعات والربح شهريًا</h2></div>
        <a class="text-link" href="/sales">تقرير المبيعات الكامل <?= Ui::icon('chevron-left', 15) ?></a>
    </div>
    <?php if ($monthlyMargin): ?>
        <div class="chart-box">
            <div class="chart-legend"><span><i style="background:var(--primary)"></i> الإيرادات</span><span><i style="background:var(--orange)"></i> الربح</span></div>
            <canvas data-chart="line" data-label="الأداء الشهري" data-values='<?= Ui::e(json_encode($trendChart, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>'></canvas>
        </div>
    <?php else: ?>
        <div class="empty-state compact">لا توجد بيانات كافية لرسم الاتجاه.</div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-head">
        <div><span class="panel-kicker">أعمار الذمم</span><h2>ملخص المستحقات حسب المدة</h2></div>
        <a class="text-link" href="/aging">التفاصيل الكاملة <?= Ui::icon('chevron-left', 15) ?></a>
    </div>
    <?php if ($agingBuckets['total'] > 0): ?>
        <div class="metric-grid metric-grid-5">
            <?php foreach ($bucketOrder as $key): ?>
                <article class="metric-card <?= $bucketAccents[$key] ?>">
                    <div class="metric-top"><span class="metric-icon"><?= Ui::icon($key === 'current' ? 'trend' : 'warning') ?></span><span class="metric-mini"><?= Ui::number($agingBuckets['counts'][$key]) ?> فاتورة</span></div>
                    <span class="metric-label"><?= Ui::e($bucketLabels[$key]) ?></span>
                    <strong><?= Ui::money($agingBuckets[$key]) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state compact">لا توجد مستحقات لعرضها كما بتاريخ <?= Ui::e($asOf ?? '') ?>.</div>
    <?php endif; ?>
</section>

<section class="two-column-grid">
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">الأكثر إيرادًا</span><h2>أفضل العملاء</h2></div><a class="text-link" href="/customers">عرض الكل</a></div>
        <?php if ($topCustomers): ?>
            <div class="rank-list dense">
                <?php foreach ($topCustomers as $index => $customer): ?>
                    <div class="rank-row">
                        <span class="rank-no"><?= $index + 1 ?></span>
                        <div class="rank-content"><div><strong><?= Ui::e($customer['customer_name']) ?></strong><span><?= Ui::number($customer['invoices']) ?> فاتورة</span></div><div class="progress"><i style="width:<?= max(5, ($customer['revenue'] / $topCustomerMax) * 100) ?>%"></i></div></div>
                        <b><?= Ui::money($customer['revenue']) ?></b>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state compact">لا يوجد عملاء ضمن البيانات الحالية.</div>
        <?php endif; ?>
    </article>
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">الأكثر تأثيرًا</span><h2>أفضل المنتجات</h2></div><a class="text-link" href="/inventory">عرض الكل</a></div>
        <?php if ($topProducts): ?>
            <div class="rank-list dense">
                <?php foreach ($topProducts as $index => $product): ?>
                    <div class="rank-row">
                        <span class="rank-no"><?= $index + 1 ?></span>
                        <div class="rank-content"><div><strong><?= Ui::e($product['name']) ?></strong><span><?= Ui::number($product['qty']) ?> وحدة</span></div><div class="progress"><i style="width:<?= max(5, ($product['revenue'] / $topProductMax) * 100) ?>%"></i></div></div>
                        <b><?= Ui::money($product['revenue']) ?></b>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state compact">لا توجد منتجات ضمن البيانات الحالية.</div>
        <?php endif; ?>
    </article>
</section>
