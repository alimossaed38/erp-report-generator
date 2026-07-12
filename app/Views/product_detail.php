<?php
/** @var bool $found */
if (!$found): ?>
<div class="empty-state">
    <?= Ui::icon('box', 30) ?>
    <strong>لم يتم العثور على المنتج</strong>
    <span><?= $id ? 'لا يوجد منتج بالمعرّف "' . Ui::e((string) $id) . '".' : 'يرجى تحديد معرّف منتج صالح.' ?> جرّب العودة إلى <a href="/inventory">قائمة المخزون</a>.</span>
</div>
<?php return; endif; ?>
<div class="report-toolbar">
    <div class="result-summary"><strong><?= Ui::e($product['name']) ?></strong><span><?= Ui::e($product['category']) ?></span></div>
    <div class="export-actions">
        <a class="btn ghost" href="/inventory"><?= Ui::icon('reset', 17) ?> العودة للمخزون</a>
        <button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button>
    </div>
</div>

<section class="metric-grid metric-grid-6">
    <article class="metric-card accent-blue"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('box') ?></span></div><span class="metric-label">المخزون الحالي</span><strong><?= Ui::number($product['stock_qty']) ?></strong><small>حد إعادة الطلب: <?= Ui::number($product['reorder_level']) ?></small></article>
    <article class="metric-card accent-violet"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('sales') ?></span></div><span class="metric-label">الكمية المباعة</span><strong><?= Ui::number($product['sold_qty']) ?></strong><small>إجمالي الوحدات المباعة</small></article>
    <article class="metric-card accent-green"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('finance') ?></span></div><span class="metric-label">الإيرادات</span><strong><?= Ui::money($product['revenue']) ?></strong><small>تكلفة البضاعة: <?= Ui::money($product['cogs']) ?></small></article>
    <article class="metric-card accent-orange"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('trend') ?></span></div><span class="metric-label">صافي الربح</span><strong><?= Ui::money($product['profit']) ?></strong><small>الإيرادات - التكلفة</small></article>
    <article class="metric-card accent-blue"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('target') ?></span></div><span class="metric-label">هامش الربح</span><strong><?= Ui::percent($product['margin_pct']) ?></strong><small>الربح ÷ الإيرادات</small></article>
    <article class="metric-card <?= $product['out'] ? 'accent-red' : ($product['low'] ? 'accent-orange' : 'accent-green') ?>"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('warning') ?></span></div><span class="metric-label">الحالة</span><strong>
        <?php if ($product['out']): ?><span class="badge danger"><i></i> نفد المخزون</span>
        <?php elseif ($product['low']): ?><span class="badge warning"><i></i> منخفض</span>
        <?php else: ?><span class="badge success"><i></i> متوفر</span><?php endif; ?>
    </strong><small>سعر البيع <?= Ui::money($product['price']) ?> · التكلفة <?= Ui::money($product['cost']) ?></small></article>
</section>

<article class="panel">
    <div class="panel-head"><div><span class="panel-kicker">الأداء عبر الزمن</span><h2>اتجاه المبيعات الشهرية</h2></div></div>
    <?php if ($monthly): ?><div class="chart-box chart-lg"><canvas data-chart="bar" data-label="الإيرادات" data-values='<?= Ui::e(json_encode(array_map(static fn($m) => ['label' => $m['ym'], 'value' => $m['revenue']], $monthly), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>'></canvas></div><?php else: ?><div class="empty-state compact">لا توجد بيانات كافية لرسم الاتجاه.</div><?php endif; ?>
</article>

<section class="panel table-panel">
    <div class="panel-head"><div><span class="panel-kicker">سجل المبيعات</span><h2>فواتير المنتج</h2></div><span class="table-count"><?= Ui::number(count($invoices)) ?> سجل</span></div>
    <?php if ($invoices): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>الكمية</th><th>الإجمالي</th></tr></thead><tbody>
        <?php foreach ($invoices as $invoice): ?><tr><td><strong><a href="<?= Ui::e(Ui::url('/invoices/view', ['no' => $invoice['invoice_no']])) ?>"><?= Ui::e($invoice['invoice_no']) ?></a></strong></td><td><?= Ui::e($invoice['invoice_date']) ?></td><td><?= Ui::number($invoice['qty']) ?></td><td class="money-cell"><?= Ui::money($invoice['line_total']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><?= Ui::icon('search', 30) ?><strong>لا توجد مبيعات</strong><span>لم يُباع هذا المنتج ضمن أي فاتورة بعد.</span></div><?php endif; ?>
</section>
