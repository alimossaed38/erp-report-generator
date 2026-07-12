<?php
/** @var array $buckets @var array $bucketLabels @var array $invoices @var ?string $asOf */
$bucketOrder = ['current', 'd1_30', 'd31_60', 'd61_90', 'd90_plus'];
$bucketAccents = [
    'current' => 'accent-green',
    'd1_30' => 'accent-blue',
    'd31_60' => 'accent-orange',
    'd61_90' => 'accent-red',
    'd90_plus' => 'accent-violet',
];
$bucketIcons = [
    'current' => 'trend',
    'd1_30' => 'calendar',
    'd31_60' => 'calendar',
    'd61_90' => 'warning',
    'd90_plus' => 'warning',
];
$chartValues = array_map(static fn(string $key) => ['label' => $bucketLabels[$key], 'value' => (float) $buckets[$key]], $bucketOrder);
?>
<div class="report-toolbar">
    <div class="result-summary"><strong><?= Ui::number(count($invoices)) ?></strong> فاتورة مستحقة<?php if ($asOf): ?><span>كما بتاريخ <?= Ui::e($asOf) ?></span><?php endif; ?></div>
    <div class="export-actions">
        <button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button>
    </div>
</div>

<section class="metric-grid metric-grid-5">
    <?php foreach ($bucketOrder as $key): ?>
        <article class="metric-card <?= $bucketAccents[$key] ?>">
            <div class="metric-top"><span class="metric-icon"><?= Ui::icon($bucketIcons[$key]) ?></span><span class="metric-mini"><?= Ui::number($buckets['counts'][$key]) ?> فاتورة</span></div>
            <span class="metric-label"><?= Ui::e($bucketLabels[$key]) ?></span>
            <strong><?= Ui::money($buckets[$key]) ?></strong>
            <small><?= $key === 'current' ? 'ضمن مهلة السداد' : 'متأخر السداد' ?></small>
        </article>
    <?php endforeach; ?>
</section>

<section class="panel">
    <div class="panel-head"><div><span class="panel-kicker">إجمالي المستحقات</span><h2>إجمالي المستحق</h2></div></div>
    <div class="metric-grid metric-grid-4">
        <article class="metric-card accent-blue"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('receipt') ?></span></div><span class="metric-label">إجمالي المستحق</span><strong><?= Ui::money($buckets['total']) ?></strong><small>مجموع كل فترات التأخير</small></article>
    </div>
</section>

<article class="panel">
    <div class="panel-head"><div><span class="panel-kicker">التوزيع حسب المدة</span><h2>أعمار الذمم المدينة</h2></div></div>
    <?php if ($buckets['total'] > 0): ?>
        <div class="chart-box chart-lg"><canvas data-chart="bar" data-label="المستحق" data-values='<?= Ui::e(json_encode($chartValues, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>'></canvas></div>
    <?php else: ?>
        <div class="empty-state">لا توجد مستحقات لعرضها.</div>
    <?php endif; ?>
</article>

<section class="panel table-panel">
    <div class="panel-head"><div><span class="panel-kicker">تفاصيل الفواتير</span><h2>الفواتير المستحقة</h2></div><span class="table-count"><?= Ui::number(count($invoices)) ?> سجل</span></div>
    <?php if ($invoices): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>العميل</th><th>رقم الفاتورة</th><th>تاريخ الفاتورة</th><th>الاستحقاق</th><th>الفئة</th><th>أيام التأخير</th><th>المستحق</th></tr></thead><tbody>
        <?php foreach ($invoices as $invoice): ?><tr>
            <td><a href="<?= Ui::e(Ui::url('/customers/view', ['name' => $invoice['customer_name']])) ?>"><?= Ui::e($invoice['customer_name']) ?></a></td>
            <td><strong><a href="<?= Ui::e(Ui::url('/invoices/view', ['no' => $invoice['invoice_no']])) ?>"><?= Ui::e($invoice['invoice_no']) ?></a></strong></td>
            <td><?= Ui::e($invoice['invoice_date']) ?></td>
            <td><?= Ui::e($invoice['due_date']) ?></td>
            <td><span class="badge <?= $invoice['bucket'] === 'current' ? 'success' : ($invoice['bucket'] === 'd90_plus' ? 'danger' : 'warning') ?>"><i></i> <?= Ui::e($invoice['bucket_label']) ?></span></td>
            <td><?= $invoice['days_late'] > 0 ? Ui::number($invoice['days_late']) : '—' ?></td>
            <td class="money-cell"><?= Ui::money($invoice['outstanding']) ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><?= Ui::icon('search', 30) ?><strong>لا توجد مستحقات</strong><span>جميع الفواتير مسددة بالكامل.</span></div><?php endif; ?>
</section>
