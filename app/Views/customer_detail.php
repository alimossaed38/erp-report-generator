<?php
/** @var bool $found */
if (!$found): ?>
<div class="empty-state">
    <?= Ui::icon('users', 30) ?>
    <strong>لم يتم العثور على العميل</strong>
    <span><?= $name ? 'لا توجد بيانات لعميل باسم "' . Ui::e($name) . '".' : 'يرجى تحديد اسم عميل صالح.' ?> جرّب العودة إلى <a href="/customers">قائمة العملاء</a>.</span>
</div>
<?php return; endif; ?>
<div class="report-toolbar">
    <div class="result-summary"><strong><?= Ui::e($customer['customer_name']) ?></strong><span>ملخص أداء العميل</span></div>
    <div class="export-actions">
        <a class="btn ghost" href="/customers"><?= Ui::icon('reset', 17) ?> العودة للعملاء</a>
        <button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button>
    </div>
</div>

<section class="metric-grid metric-grid-5">
    <article class="metric-card accent-blue"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('receipt') ?></span></div><span class="metric-label">عدد الفواتير</span><strong><?= Ui::number($customer['invoices']) ?></strong><small>إجمالي عدد المعاملات</small></article>
    <article class="metric-card accent-violet"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('sales') ?></span></div><span class="metric-label">إجمالي الإيرادات</span><strong><?= Ui::money($customer['revenue']) ?></strong><small>منذ أول عملية شراء</small></article>
    <article class="metric-card accent-orange"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('target') ?></span></div><span class="metric-label">متوسط الفاتورة</span><strong><?= Ui::money($customer['avg']) ?></strong><small>مؤشر قيمة السلة</small></article>
    <article class="metric-card <?= $customer['outstanding'] > 0.005 ? 'accent-red' : 'accent-green' ?>"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('warning') ?></span></div><span class="metric-label">المستحق</span><strong><?= Ui::money($customer['outstanding']) ?></strong><small><?= $customer['outstanding'] > 0.005 ? 'مبالغ غير مسددة' : 'لا توجد مستحقات' ?></small></article>
    <article class="metric-card accent-green"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('calendar') ?></span></div><span class="metric-label">آخر عملية شراء</span><strong><?= Ui::e($customer['last_purchase']) ?></strong><small>أول عملية: <?= Ui::e($customer['first_purchase']) ?></small></article>
</section>

<article class="panel">
    <div class="panel-head"><div><span class="panel-kicker">الأداء عبر الزمن</span><h2>اتجاه المشتريات الشهرية</h2></div></div>
    <?php if ($monthly): ?><div class="chart-box chart-lg"><canvas data-chart="bar" data-label="المشتريات" data-values='<?= Ui::e(json_encode(array_map(static fn($m) => ['label' => $m['ym'], 'value' => $m['total']], $monthly), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>'></canvas></div><?php else: ?><div class="empty-state compact">لا توجد بيانات كافية لرسم الاتجاه.</div><?php endif; ?>
</article>

<section class="panel table-panel">
    <div class="panel-head"><div><span class="panel-kicker">سجل المعاملات</span><h2>فواتير العميل</h2></div><span class="table-count"><?= Ui::number(count($invoices)) ?> سجل</span></div>
    <?php if ($invoices): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>الاستحقاق</th><th>الإجمالي</th><th>المدفوع</th><th>المستحق</th><th>الحالة</th></tr></thead><tbody>
        <?php foreach ($invoices as $invoice): ?><tr><td><strong><a href="<?= Ui::e(Ui::url('/invoices/view', ['no' => $invoice['invoice_no']])) ?>"><?= Ui::e($invoice['invoice_no']) ?></a></strong></td><td><?= Ui::e($invoice['invoice_date']) ?></td><td><?= Ui::e($invoice['due_date']) ?></td><td class="money-cell"><?= Ui::money($invoice['total']) ?></td><td class="money-cell"><?= Ui::money($invoice['amount_paid']) ?></td><td class="money-cell"><?= Ui::money($invoice['outstanding']) ?></td><td>
            <?php if ($invoice['pay_status'] === 'paid'): ?><span class="badge success"><i></i> مسدد</span>
            <?php elseif ($invoice['pay_status'] === 'partial'): ?><span class="badge warning"><i></i> جزئي</span>
            <?php else: ?><span class="badge danger"><i></i> غير مسدد</span><?php endif; ?>
        </td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><?= Ui::icon('search', 30) ?><strong>لا توجد فواتير</strong><span>لا يوجد سجل معاملات لهذا العميل.</span></div><?php endif; ?>
</section>
