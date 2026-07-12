<?php
/** @var array $summary @var array $products @var array $byCategory @var array $categories */
?>
<form method="get" class="filters">
    <label>التصنيف:
        <select name="category">
            <option value="">الكل</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= ($category ?? '')===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">تطبيق</button>
    <a class="export" href="/export?report=inventory&format=csv&category=<?= urlencode($category ?? '') ?>">تصدير CSV</a>
    <a class="export" href="/export?report=inventory&format=xls&category=<?= urlencode($category ?? '') ?>">تصدير Excel</a>
    <button type="button" onclick="window.print()">طباعة / PDF</button>
</form>

<section class="kpis">
    <div class="kpi"><span class="kpi-label">إجمالي الأصناف</span><span class="kpi-value"><?= (int)$summary['items'] ?></span></div>
    <div class="kpi"><span class="kpi-label">قيمة المخزون</span><span class="kpi-value"><?= money($summary['value']) ?></span></div>
    <div class="kpi <?= $summary['low']>0?'bad':'good' ?>"><span class="kpi-label">أصناف ناقصة</span><span class="kpi-value"><?= (int)$summary['low'] ?></span></div>
</section>

<section class="card">
    <h2>قيمة المخزون حسب التصنيف</h2>
    <canvas id="invChart" data-type="bar" data-label="القيمة"
      data-values='<?= json_encode(array_map(fn($r)=>["label"=>$r["category"],"value"=>$r["value"]], $byCategory), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>'></canvas>
</section>

<section class="card">
    <h2>المنتجات</h2>
    <table>
        <thead><tr><th>المنتج</th><th>التصنيف</th><th>الكمية</th><th>حد الطلب</th><th>سعر البيع</th><th>الحالة</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr class="<?= $p['low']?'low':'' ?>">
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td><?= (int)$p['stock_qty'] ?></td>
                <td><?= (int)$p['reorder_level'] ?></td>
                <td><?= money($p['price']) ?></td>
                <td><span class="badge <?= $p['low']?'low':'ok' ?>"><?= $p['low']?'ناقص':'متوفر' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
