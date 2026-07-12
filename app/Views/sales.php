<?php
/** @var array $summary @var array $monthly @var array $top @var array $invoices */
?>
<form method="get" class="filters">
    <label>من: <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>"></label>
    <label>إلى: <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>"></label>
    <button type="submit">تطبيق</button>
    <a class="export" href="/export?report=sales&format=csv&from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">تصدير CSV</a>
    <a class="export" href="/export?report=sales&format=xls&from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">تصدير Excel</a>
    <button type="button" onclick="window.print()">طباعة / PDF</button>
</form>

<section class="kpis">
    <div class="kpi"><span class="kpi-label">إجمالي المبيعات</span><span class="kpi-value"><?= money($summary['total']) ?></span></div>
    <div class="kpi"><span class="kpi-label">عدد الفواتير</span><span class="kpi-value"><?= (int)$summary['count'] ?></span></div>
    <div class="kpi"><span class="kpi-label">متوسط الفاتورة</span><span class="kpi-value"><?= money($summary['avg']) ?></span></div>
</section>

<section class="card">
    <h2>المبيعات الشهرية</h2>
    <canvas id="salesChart" data-values='<?= json_encode(array_map(fn($m)=>["label"=>$m["ym"],"value"=>$m["total"]], $monthly), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>'></canvas>
</section>

<section class="card">
    <h2>أفضل المنتجات</h2>
    <table>
        <thead><tr><th>المنتج</th><th>الكمية</th><th>الإيراد</th></tr></thead>
        <tbody>
        <?php foreach ($top as $t): ?>
            <tr><td><?= htmlspecialchars($t['name']) ?></td><td><?= (int)$t['qty'] ?></td><td><?= money($t['revenue']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>آخر الفواتير</h2>
    <table>
        <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>التاريخ</th><th>الإجمالي</th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $inv): ?>
            <tr><td><?= htmlspecialchars($inv['invoice_no']) ?></td><td><?= htmlspecialchars($inv['customer_name']) ?></td><td><?= htmlspecialchars($inv['invoice_date']) ?></td><td><?= money($inv['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
