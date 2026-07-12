<?php
/** @var array $rows @var array $pagination */
$params = ['from' => $from, 'to' => $to, 'q' => $q, 'per_page' => $perPage, 'sort' => $sort, 'dir' => $direction];
$sortLink = static function (string $key, string $label) use ($params, $sort, $direction): string {
    $next = ($sort === $key && $direction === 'asc') ? 'desc' : 'asc';
    $icon = $sort === $key ? ($direction === 'asc' ? Ui::icon('arrow-up', 13) : Ui::icon('arrow-down', 13)) : '';
    return '<a class="sort-link" href="' . Ui::e(Ui::url('', array_merge($params, ['sort' => $key, 'dir' => $next, 'page' => 1]))) . '">' . Ui::e($label) . $icon . '</a>';
};
?>
<section class="filter-panel">
    <div class="filter-panel-head">
        <div><span class="filter-icon"><?= Ui::icon('filter') ?></span><div><strong>تصفية بيانات العملاء</strong><small>غيّر الفترة أو ابحث باسم العميل</small></div></div>
        <div class="quick-ranges">
            <?php foreach ($quickRanges as $range): ?><a href="<?= Ui::e(Ui::url('/customers', ['from' => $range['from'], 'to' => $range['to']])) ?>"><?= Ui::e($range['label']) ?></a><?php endforeach; ?>
        </div>
    </div>
    <form method="get" class="filter-grid">
        <label class="field field-search"><span>بحث سريع</span><div class="input-icon"><?= Ui::icon('search', 17) ?><input type="search" name="q" value="<?= Ui::e($q ?? '') ?>" placeholder="اسم العميل"></div></label>
        <label class="field"><span>من تاريخ</span><input type="date" name="from" value="<?= Ui::e($from ?? '') ?>" min="<?= Ui::e($bounds['min'] ?? '') ?>" max="<?= Ui::e($bounds['max'] ?? '') ?>"></label>
        <label class="field"><span>إلى تاريخ</span><input type="date" name="to" value="<?= Ui::e($to ?? '') ?>" min="<?= Ui::e($bounds['min'] ?? '') ?>" max="<?= Ui::e($bounds['max'] ?? '') ?>"></label>
        <label class="field field-small"><span>عدد الصفوف</span><select name="per_page"><?php foreach (Config::get('per_page_options', [10,25,50,100]) as $option): ?><option value="<?= $option ?>" <?= $perPage === (int) $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label>
        <input type="hidden" name="sort" value="<?= Ui::e($sort) ?>"><input type="hidden" name="dir" value="<?= Ui::e($direction) ?>">
        <div class="filter-actions"><button class="btn primary" type="submit"><?= Ui::icon('filter', 17) ?> تطبيق</button><a class="btn ghost" href="/customers"><?= Ui::icon('reset', 17) ?> إعادة ضبط</a></div>
    </form>
</section>

<div class="report-toolbar">
    <div class="result-summary"><strong><?= Ui::number($pagination['total']) ?></strong> عميل مطابق<?php if ($from || $to): ?><span>خلال الفترة المحددة</span><?php endif; ?></div>
    <div class="export-actions">
        <button class="btn dark" type="button" data-print><?= Ui::icon('printer', 17) ?> طباعة / PDF</button>
    </div>
</div>

<section class="panel table-panel">
    <div class="panel-head"><div><span class="panel-kicker">سجل العملاء</span><h2>أداء العملاء</h2></div><span class="table-count"><?= Ui::number($pagination['total']) ?> سجل</span></div>
    <?php if ($rows): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th><?= $sortLink('customer_name', 'العميل') ?></th><th><?= $sortLink('invoices', 'عدد الفواتير') ?></th><th><?= $sortLink('revenue', 'الإيرادات') ?></th><th>المتوسط</th><th><?= $sortLink('last_purchase', 'آخر عملية شراء') ?></th><th>المستحق</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?><tr><td><strong><a href="<?= Ui::e(Ui::url('/customers/view', ['name' => $row['customer_name']])) ?>"><?= Ui::e($row['customer_name']) ?></a></strong></td><td><?= Ui::number($row['invoices']) ?></td><td class="money-cell"><?= Ui::money($row['revenue']) ?></td><td class="money-cell"><?= Ui::money($row['avg']) ?></td><td><?= Ui::e($row['last_purchase']) ?></td><td class="money-cell"><?php if ($row['outstanding'] > 0.005): ?><span class="badge warning"><i></i> <?= Ui::money($row['outstanding']) ?></span><?php else: ?><span class="badge success"><i></i> مسدد</span><?php endif; ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php $baseParams = $params; require __DIR__ . '/partials/pagination.php'; ?>
    <?php else: ?><div class="empty-state"><?= Ui::icon('search', 30) ?><strong>لا يوجد عملاء مطابقون</strong><span>جرّب تغيير الفترة أو إزالة كلمة البحث.</span></div><?php endif; ?>
</section>
