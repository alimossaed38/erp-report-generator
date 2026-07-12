<?php
/** @var array $salesSummary @var array $invSummary @var array $finSummary @var array $salesMonthly @var array $finMonthly @var array $recentInvoices */
?>
<section class="kpis">
    <div class="kpi"><span class="kpi-label">إجمالي المبيعات</span><span class="kpi-value"><?= money($salesSummary['total']) ?></span></div>
    <div class="kpi <?= $finSummary['net']>=0?'good':'bad' ?>"><span class="kpi-label">صافي الربح</span><span class="kpi-value"><?= money($finSummary['net']) ?></span></div>
    <div class="kpi"><span class="kpi-label">قيمة المخزون</span><span class="kpi-value"><?= money($invSummary['value']) ?></span></div>
    <div class="kpi <?= $invSummary['low']>0?'warn':'good' ?>"><span class="kpi-label">أصناف ناقصة</span><span class="kpi-value"><?= (int)$invSummary['low'] ?></span></div>
</section>

<section class="card">
    <h2>المبيعات الشهرية</h2>
    <canvas id="dashSales" data-type="line" data-label="المبيعات"
      data-values='<?= json_encode(array_map(fn($m)=>["label"=>$m["ym"],"value"=>$m["total"]], $salesMonthly), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>'></canvas>
</section>

<section class="card">
    <h2>الإيرادات مقابل المصروفات</h2>
    <canvas id="dashFin" data-type="bar"
      data-values='<?= json_encode(array_map(fn($m)=>["label"=>$m["ym"],"series"=>$m["series"]], $finMonthly), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>'></canvas>
</section>

<section class="card">
    <h2>آخر الفواتير</h2>
    <table>
        <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>التاريخ</th><th>الإجمالي</th></tr></thead>
        <tbody>
        <?php foreach ($recentInvoices as $inv): ?>
            <tr><td><?= htmlspecialchars($inv['invoice_no']) ?></td><td><?= htmlspecialchars($inv['customer_name']) ?></td><td><?= htmlspecialchars($inv['invoice_date']) ?></td><td><?= money($inv['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
