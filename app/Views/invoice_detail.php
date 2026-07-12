<?php
/** @var bool $found */
if (!$found): ?>
<div class="empty-state">
    <?= Ui::icon('receipt', 30) ?>
    <strong>لم يتم العثور على الفاتورة</strong>
    <span><?= $no ? 'لا توجد فاتورة برقم "' . Ui::e($no) . '".' : 'يرجى تحديد رقم فاتورة صالح.' ?> جرّب العودة إلى <a href="/sales">قائمة المبيعات</a>.</span>
</div>
<?php return; endif; ?>
<div class="report-toolbar">
    <div class="result-summary"><strong><?= Ui::e($invoice['invoice_no']) ?></strong><span>تفاصيل الفاتورة</span></div>
    <div class="export-actions">
        <a class="btn ghost" href="/sales"><?= Ui::icon('reset', 17) ?> العودة للمبيعات</a>
        <button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button>
    </div>
</div>

<section class="panel">
    <div class="panel-head"><div><span class="panel-kicker">بيانات الفاتورة</span><h2>ملخص الفاتورة</h2></div>
        <?php if ($invoice['pay_status'] === 'paid'): ?><span class="badge success"><i></i> مسدد</span>
        <?php elseif ($invoice['pay_status'] === 'partial'): ?><span class="badge warning"><i></i> جزئي</span>
        <?php else: ?><span class="badge danger"><i></i> غير مسدد</span><?php endif; ?>
    </div>
    <div class="invoice-meta-grid">
        <div><span>العميل</span><strong><a href="<?= Ui::e(Ui::url('/customers/view', ['name' => $invoice['customer_name']])) ?>"><?= Ui::e($invoice['customer_name']) ?></a></strong></div>
        <div><span>تاريخ الفاتورة</span><strong><?= Ui::e($invoice['invoice_date']) ?></strong></div>
        <div><span>تاريخ الاستحقاق</span><strong><?= Ui::e($invoice['due_date']) ?></strong></div>
        <div><span>الإجمالي</span><strong><?= Ui::money($invoice['total']) ?></strong></div>
        <div><span>المدفوع</span><strong><?= Ui::money($invoice['amount_paid']) ?></strong></div>
        <div><span>المستحق</span><strong><?= Ui::money($invoice['outstanding']) ?></strong></div>
    </div>
</section>

<section class="panel table-panel">
    <div class="panel-head"><div><span class="panel-kicker">تفاصيل الأصناف</span><h2>بنود الفاتورة</h2></div><span class="table-count"><?= Ui::number(count($items)) ?> بند</span></div>
    <?php if ($items): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead><tbody>
        <?php foreach ($items as $item): ?><tr><td><strong><?= Ui::e($item['name']) ?></strong></td><td><?= Ui::number($item['qty']) ?></td><td class="money-cell"><?= Ui::money($item['unit_price']) ?></td><td class="money-cell"><?= Ui::money($item['line_total']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <div class="table-footer"><p>إجمالي الفاتورة: <strong><?= Ui::money($invoice['total']) ?></strong></p></div>
    <?php else: ?><div class="empty-state"><?= Ui::icon('search', 30) ?><strong>لا توجد بنود</strong><span>لا تحتوي هذه الفاتورة على أي أصناف.</span></div><?php endif; ?>
</section>
