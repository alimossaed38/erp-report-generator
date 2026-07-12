<?php
/** @var array $summary @var array $monthly @var array $transactions */
$fmt = fn($n) => number_format((float)$n, 0) . ' ر.س';
?>
<form method="get" class="filters">
    <label>من: <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>"></label>
    <label>إلى: <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>"></label>
    <button type="submit">تطبيق</button>
    <a class="export" href="/export?report=finance&format=csv&from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">تصدير CSV</a>
    <a class="export" href="/export?report=finance&format=xls&from=<?= urlencode($from ?? '') ?>&to=<?= urlencode($to ?? '') ?>">تصدير Excel</a>
    <button type="button" onclick="window.print()">طباعة / PDF</button>
</form>

<section class="kpis">
    <div class="kpi good"><span class="kpi-label">الإيرادات</span><span class="kpi-value"><?= $fmt($summary['income']) ?></span></div>
    <div class="kpi bad"><span class="kpi-label">المصروفات</span><span class="kpi-value"><?= $fmt($summary['expense']) ?></span></div>
    <div class="kpi <?= $summary['net']>=0?'good':'bad' ?>"><span class="kpi-label">صافي الربح</span><span class="kpi-value"><?= $fmt($summary['net']) ?></span></div>
</section>

<section class="card">
    <h2>الإيرادات مقابل المصروفات (شهرياً)</h2>
    <canvas id="finChart" data-type="bar"
      data-values='<?= json_encode(array_map(fn($m)=>["label"=>$m["ym"],"series"=>$m["series"]], $monthly), JSON_UNESCAPED_UNICODE) ?>'></canvas>
</section>

<section class="card">
    <h2>آخر الحركات المالية</h2>
    <table>
        <thead><tr><th>التاريخ</th><th>النوع</th><th>التصنيف</th><th>المبلغ</th><th>الوصف</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['txn_date']) ?></td>
                <td><span class="badge <?= $t['type']==='income'?'ok':'low' ?>"><?= $t['type']==='income'?'إيراد':'مصروف' ?></span></td>
                <td><?= htmlspecialchars($t['category']) ?></td>
                <td><?= $fmt($t['amount']) ?></td>
                <td><?= htmlspecialchars($t['description']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
