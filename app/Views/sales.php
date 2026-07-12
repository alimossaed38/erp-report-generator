<?php
/** @var array $summary @var array $monthly @var array $top @var array $invoices @var array $pagination */
$params = ['from' => $from, 'to' => $to, 'q' => $q, 'per_page' => $perPage, 'sort' => $sort, 'dir' => $direction];
$exportParams = ['report' => 'sales', 'from' => $from, 'to' => $to, 'q' => $q];
$change = static fn(string $key): ?float => $previousSummary ? Report::change((float) $summary[$key], (float) $previousSummary[$key]) : null;
$sortLink = static function (string $key, string $label) use ($params, $sort, $direction): string {
    $next = ($sort === $key && $direction === 'asc') ? 'desc' : 'asc';
    $icon = $sort === $key ? ($direction === 'asc' ? Ui::icon('arrow-up', 13) : Ui::icon('arrow-down', 13)) : '';
    return '<a class="sort-link" href="' . Ui::e(Ui::url('', array_merge($params, ['sort' => $key, 'dir' => $next, 'page' => 1]))) . '">' . Ui::e($label) . $icon . '</a>';
};
$topMax = !empty($top) ? max(array_column($top, 'revenue')) : 1;
?>
<section class="filter-panel">
    <div class="filter-panel-head">
        <div><span class="filter-icon"><?= Ui::icon('filter') ?></span><div><strong>تصفية بيانات المبيعات</strong><small>غيّر الفترة أو ابحث برقم الفاتورة واسم العميل</small></div></div>
        <div class="quick-ranges">
            <?php foreach ($quickRanges as $range): ?><a href="<?= Ui::e(Ui::url('/sales', ['from' => $range['from'], 'to' => $range['to']])) ?>"><?= Ui::e($range['label']) ?></a><?php endforeach; ?>
        </div>
    </div>
    <form method="get" class="filter-grid">
        <label class="field field-search"><span>بحث سريع</span><div class="input-icon"><?= Ui::icon('search', 17) ?><input type="search" name="q" value="<?= Ui::e($q ?? '') ?>" placeholder="رقم الفاتورة أو العميل"></div></label>
        <label class="field"><span>من تاريخ</span><input type="date" name="from" value="<?= Ui::e($from ?? '') ?>" min="<?= Ui::e($bounds['min'] ?? '') ?>" max="<?= Ui::e($bounds['max'] ?? '') ?>"></label>
        <label class="field"><span>إلى تاريخ</span><input type="date" name="to" value="<?= Ui::e($to ?? '') ?>" min="<?= Ui::e($bounds['min'] ?? '') ?>" max="<?= Ui::e($bounds['max'] ?? '') ?>"></label>
        <label class="field field-small"><span>عدد الصفوف</span><select name="per_page"><?php foreach (Config::get('per_page_options', [10,25,50,100]) as $option): ?><option value="<?= $option ?>" <?= $perPage === (int) $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label>
        <input type="hidden" name="sort" value="<?= Ui::e($sort) ?>"><input type="hidden" name="dir" value="<?= Ui::e($direction) ?>">
        <div class="filter-actions"><button class="btn primary" type="submit"><?= Ui::icon('filter', 17) ?> تطبيق</button><a class="btn ghost" href="/sales"><?= Ui::icon('reset', 17) ?> إعادة ضبط</a></div>
    </form>
</section>

<div class="report-toolbar">
    <div class="result-summary"><strong><?= Ui::number($pagination['total']) ?></strong> فاتورة مطابقة<?php if ($from || $to): ?><span>خلال الفترة المحددة</span><?php endif; ?></div>
    <div class="export-actions">
        <a class="btn soft" href="<?= Ui::e(Ui::url('/export', array_merge($exportParams, ['format' => 'csv']))) ?>"><?= Ui::icon('download', 17) ?> CSV</a>
        <a class="btn soft" href="<?= Ui::e(Ui::url('/export', array_merge($exportParams, ['format' => 'xls']))) ?>"><?= Ui::icon('download', 17) ?> Excel</a>
        <button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button>
    </div>
</div>

<section class="metric-grid metric-grid-6">
    <article class="metric-card accent-blue"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('sales') ?></span><?php if ($previousSummary): ?><span class="metric-trend <?= ($change('total') ?? 0) >= 0 ? 'up' : 'down' ?>"><?= Ui::percent($change('total')) ?></span><?php endif; ?></div><span class="metric-label">إجمالي المبيعات</span><strong><?= Ui::money($summary['total']) ?></strong><small><?= $previousSummary ? 'مقارنة بالفترة السابقة' : 'إجمالي الفترة الحالية' ?></small></article>
    <article class="metric-card accent-violet"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('receipt') ?></span><?php if ($previousSummary): ?><span class="metric-trend <?= ($change('count') ?? 0) >= 0 ? 'up' : 'down' ?>"><?= Ui::percent($change('count')) ?></span><?php endif; ?></div><span class="metric-label">عدد الفواتير</span><strong><?= Ui::number($summary['count']) ?></strong><small>متوسط <?= Ui::money($summary['avg']) ?> للفاتورة</small></article>
    <article class="metric-card accent-green"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('users') ?></span><span class="metric-mini">عملاء فريدون</span></div><span class="metric-label">عدد العملاء</span><strong><?= Ui::number($summary['customers']) ?></strong><small>ضمن نتائج البحث والفترة</small></article>
    <article class="metric-card accent-orange"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('target') ?></span><?php if ($previousSummary): ?><span class="metric-trend <?= ($change('avg') ?? 0) >= 0 ? 'up' : 'down' ?>"><?= Ui::percent($change('avg')) ?></span><?php endif; ?></div><span class="metric-label">متوسط قيمة الفاتورة</span><strong><?= Ui::money($summary['avg']) ?></strong><small>مؤشر جودة السلة البيعية</small></article>
    <article class="metric-card accent-green"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('finance') ?></span><span class="metric-mini">التكلفة مطروحة</span></div><span class="metric-label">صافي الربح</span><strong><?= Ui::money($margin['profit']) ?></strong><small>الإيرادات - تكلفة البضاعة المباعة</small></article>
    <article class="metric-card accent-blue"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('finance') ?></span><span class="metric-mini">هامش الربح</span></div><span class="metric-label">نسبة الهامش</span><strong><?= Ui::percent($margin['margin_pct']) ?></strong><small>الربح ÷ الإيرادات</small></article>
</section>

<section class="panel">
    <div class="panel-head"><div><span class="panel-kicker">الأداء مقابل الخطة</span><h2>تقدّم هدف المبيعات</h2></div></div>
    <div class="target-progress">
        <div class="target-progress-head"><span>نسبة الإنجاز</span><strong><?= Ui::percent($target['pct']) ?></strong></div>
        <div class="progress<?= ($target['pct'] !== null && $target['pct'] < 60) ? ' danger' : '' ?>"><i style="width:<?= $target['pct'] !== null ? min(100, max(0, $target['pct'])) : 0 ?>%"></i></div>
        <div class="target-progress-foot"><span>الفعلي: <?= Ui::money($target['actual']) ?></span><span>الهدف: <?= $target['target'] !== null ? Ui::money($target['target']) : '—' ?></span></div>
    </div>
</section>

<section class="two-column-grid wide-first">
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">الأداء عبر الزمن</span><h2>اتجاه المبيعات الشهرية</h2></div><span class="date-range-note"><?= Ui::e($from ?: $bounds['min']) ?> — <?= Ui::e($to ?: $bounds['max']) ?></span></div>
        <?php if ($monthly): ?><div class="chart-box chart-lg"><canvas data-chart="bar" data-label="المبيعات" data-values='<?= Ui::e(json_encode(array_map(static fn($m) => ['label' => $m['ym'], 'value' => $m['total']], $monthly), JSON_UNESCAPED_UNICODE)) ?>'></canvas></div><?php else: ?><div class="empty-state">لا توجد بيانات كافية لرسم الاتجاه.</div><?php endif; ?>
    </article>
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">ترتيب المنتجات</span><h2>الأعلى إيرادًا</h2></div></div>
        <?php if ($top): ?><div class="rank-list dense"><?php foreach ($top as $index => $product): ?><div class="rank-row"><span class="rank-no"><?= $index + 1 ?></span><div class="rank-content"><div><strong><?= Ui::e($product['name']) ?></strong><span><?= Ui::e($product['category']) ?> · <?= Ui::number($product['qty']) ?> وحدة</span></div><div class="progress"><i style="width:<?= max(5, ($product['revenue'] / $topMax) * 100) ?>%"></i></div></div><b><?= Ui::money($product['revenue']) ?></b></div><?php endforeach; ?></div><?php else: ?><div class="empty-state compact">لا توجد منتجات ضمن الفترة.</div><?php endif; ?>
    </article>
</section>

<section class="panel table-panel">
    <div class="panel-head"><div><span class="panel-kicker">سجل المبيعات</span><h2>الفواتير</h2></div><span class="table-count"><?= Ui::number($pagination['total']) ?> سجل</span></div>
    <?php if ($invoices): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th><?= $sortLink('invoice_no', 'رقم الفاتورة') ?></th><th><?= $sortLink('customer', 'العميل') ?></th><th><?= $sortLink('invoice_date', 'التاريخ') ?></th><th><?= $sortLink('total', 'الإجمالي') ?></th><th>الحالة</th></tr></thead><tbody>
        <?php foreach ($invoices as $invoice): ?><tr><td><span class="code-cell"><?= Ui::e($invoice['invoice_no']) ?></span></td><td><strong><a href="<?= Ui::e(Ui::url('/customers/view', ['name' => $invoice['customer_name']])) ?>"><?= Ui::e($invoice['customer_name']) ?></a></strong></td><td><?= Ui::e($invoice['invoice_date']) ?></td><td class="money-cell"><?= Ui::money($invoice['total']) ?></td><td><span class="badge success"><i></i> مكتملة</span></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php $baseParams = $params; require __DIR__ . '/partials/pagination.php'; ?>
    <?php else: ?><div class="empty-state"><?= Ui::icon('search', 30) ?><strong>لا توجد فواتير مطابقة</strong><span>جرّب تغيير الفترة أو إزالة كلمة البحث.</span></div><?php endif; ?>
</section>
