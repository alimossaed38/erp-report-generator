<?php
/** @var array $summary @var array $products @var array $pagination */
$params = ['category' => $category, 'q' => $q, 'status' => $status, 'per_page' => $perPage, 'sort' => $sort, 'dir' => $direction];
$exportParams = ['report' => 'inventory', 'category' => $category, 'q' => $q, 'status' => $status];
$sortLink = static function (string $key, string $label) use ($params, $sort, $direction): string {
    $next = ($sort === $key && $direction === 'asc') ? 'desc' : 'asc';
    $icon = $sort === $key ? ($direction === 'asc' ? Ui::icon('arrow-up', 13) : Ui::icon('arrow-down', 13)) : '';
    return '<a class="sort-link" href="' . Ui::e(Ui::url('', array_merge($params, ['sort' => $key, 'dir' => $next, 'page' => 1]))) . '">' . Ui::e($label) . $icon . '</a>';
};
?>
<section class="filter-panel">
    <div class="filter-panel-head"><div><span class="filter-icon"><?= Ui::icon('filter') ?></span><div><strong>تصفية المخزون</strong><small>ابحث وصنّف المنتجات حسب الحالة ومستوى المخزون</small></div></div><div class="quick-ranges"><a href="/inventory?status=low">مخزون منخفض</a><a href="/inventory?status=out">نفد المخزون</a></div></div>
    <form method="get" class="filter-grid inventory-filter-grid">
        <label class="field field-search"><span>بحث سريع</span><div class="input-icon"><?= Ui::icon('search', 17) ?><input type="search" name="q" value="<?= Ui::e($q ?? '') ?>" placeholder="اسم المنتج أو التصنيف"></div></label>
        <label class="field"><span>التصنيف</span><select name="category"><option value="">كل التصنيفات</option><?php foreach ($categories as $item): ?><option value="<?= Ui::e($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= Ui::e($item) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>حالة المخزون</span><select name="status"><option value="">كل الحالات</option><option value="available" <?= $status === 'available' ? 'selected' : '' ?>>متوفر</option><option value="low" <?= $status === 'low' ? 'selected' : '' ?>>منخفض</option><option value="out" <?= $status === 'out' ? 'selected' : '' ?>>نفد المخزون</option></select></label>
        <label class="field field-small"><span>عدد الصفوف</span><select name="per_page"><?php foreach (Config::get('per_page_options', [10,25,50,100]) as $option): ?><option value="<?= $option ?>" <?= $perPage === (int) $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label>
        <input type="hidden" name="sort" value="<?= Ui::e($sort) ?>"><input type="hidden" name="dir" value="<?= Ui::e($direction) ?>">
        <div class="filter-actions"><button class="btn primary" type="submit"><?= Ui::icon('filter', 17) ?> تطبيق</button><a class="btn ghost" href="/inventory"><?= Ui::icon('reset', 17) ?> إعادة ضبط</a></div>
    </form>
</section>

<div class="report-toolbar"><div class="result-summary"><strong><?= Ui::number($pagination['total']) ?></strong> صنف مطابق<span><?= $category ? 'التصنيف: ' . Ui::e($category) : 'كل التصنيفات' ?></span></div><div class="export-actions"><a class="btn soft" href="<?= Ui::e(Ui::url('/export', array_merge($exportParams, ['format' => 'csv']))) ?>"><?= Ui::icon('download', 17) ?> CSV</a><a class="btn soft" href="<?= Ui::e(Ui::url('/export', array_merge($exportParams, ['format' => 'xls']))) ?>"><?= Ui::icon('download', 17) ?> Excel</a><button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button></div></div>

<section class="metric-grid metric-grid-5">
    <article class="metric-card accent-blue"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('box') ?></span></div><span class="metric-label">عدد الأصناف</span><strong><?= Ui::number($summary['items']) ?></strong><small><?= Ui::number($summary['units']) ?> وحدة متاحة</small></article>
    <article class="metric-card accent-violet"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('inventory') ?></span></div><span class="metric-label">قيمة المخزون</span><strong><?= Ui::money($summary['value']) ?></strong><small>بناءً على تكلفة الشراء</small></article>
    <article class="metric-card accent-green"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('trend') ?></span></div><span class="metric-label">الربح المتوقع</span><strong><?= Ui::money($summary['potential_profit']) ?></strong><small>فرق البيع والتكلفة للمخزون</small></article>
    <article class="metric-card accent-orange"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('warning') ?></span></div><span class="metric-label">مخزون منخفض</span><strong><?= Ui::number($summary['low']) ?></strong><small>عند أو تحت حد إعادة الطلب</small></article>
    <article class="metric-card accent-red"><div class="metric-top"><span class="metric-icon"><?= Ui::icon('warning') ?></span></div><span class="metric-label">نفد المخزون</span><strong><?= Ui::number($summary['out_of_stock']) ?></strong><small>يتطلب إجراءً فوريًا</small></article>
</section>

<section class="two-column-grid">
    <article class="panel"><div class="panel-head"><div><span class="panel-kicker">توزيع رأس المال</span><h2>قيمة المخزون حسب التصنيف</h2></div></div><div class="chart-box chart-lg"><canvas data-chart="donut" data-label="قيمة المخزون" data-values='<?= Ui::e(json_encode(array_map(static fn($row) => ['label' => $row['category'], 'value' => $row['value']], $byCategory), JSON_UNESCAPED_UNICODE)) ?>'></canvas></div></article>
    <article class="panel"><div class="panel-head"><div><span class="panel-kicker">قائمة الأولوية</span><h2>أصناف تحتاج إعادة طلب</h2></div><a class="text-link" href="/inventory?status=low">عرض الكل</a></div><?php if ($lowStock): ?><div class="alert-list"><?php foreach ($lowStock as $product): ?><div class="alert-row"><span class="status-dot <?= (int) $product['stock_qty'] === 0 ? 'danger' : 'warning' ?>"></span><div><strong><?= Ui::e($product['name']) ?></strong><small><?= Ui::e($product['category']) ?></small></div><b><?= (int) $product['stock_qty'] ?> / <?= (int) $product['reorder_level'] ?></b></div><?php endforeach; ?></div><?php else: ?><div class="empty-state compact">لا توجد أصناف حرجة.</div><?php endif; ?></article>
</section>

<section class="panel table-panel">
    <div class="panel-head"><div><span class="panel-kicker">تفاصيل الأصناف</span><h2>قائمة المنتجات</h2></div><span class="table-count"><?= Ui::number($pagination['total']) ?> سجل</span></div>
    <?php if ($products): ?><div class="table-wrap"><table class="data-table"><thead><tr><th><?= $sortLink('name', 'المنتج') ?></th><th><?= $sortLink('category', 'التصنيف') ?></th><th><?= $sortLink('stock', 'المخزون') ?></th><th>حد الطلب</th><th><?= $sortLink('price', 'سعر البيع') ?></th><th><?= $sortLink('value', 'قيمة المخزون') ?></th><th>الحالة</th></tr></thead><tbody>
    <?php foreach ($products as $product): ?><tr class="<?= $product['out'] ? 'row-danger' : ($product['low'] ? 'row-warning' : '') ?>"><td><div class="product-cell"><span><?= Ui::e(Ui::slice($product['name'], 1)) ?></span><div><strong><?= Ui::e($product['name']) ?></strong><small>تكلفة <?= Ui::money($product['cost']) ?></small></div></div></td><td><span class="category-pill"><?= Ui::e($product['category']) ?></span></td><td><strong><?= Ui::number($product['stock_qty']) ?></strong></td><td><?= Ui::number($product['reorder_level']) ?></td><td class="money-cell"><?= Ui::money($product['price']) ?></td><td class="money-cell"><?= Ui::money($product['stock_value']) ?></td><td><?php if ($product['out']): ?><span class="badge danger"><i></i> نفد</span><?php elseif ($product['low']): ?><span class="badge warning"><i></i> منخفض</span><?php else: ?><span class="badge success"><i></i> متوفر</span><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php $baseParams = $params; require __DIR__ . '/partials/pagination.php'; ?><?php else: ?><div class="empty-state"><?= Ui::icon('search', 30) ?><strong>لا توجد منتجات مطابقة</strong><span>جرّب تغيير التصنيف أو حالة المخزون.</span></div><?php endif; ?>
</section>
