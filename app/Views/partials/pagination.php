<?php
/** @var array $pagination @var array $baseParams */
if (($pagination['total'] ?? 0) <= 0) {
    return;
}
$page = (int) $pagination['page'];
$pages = (int) $pagination['pages'];
$start = max(1, $page - 2);
$end = min($pages, $page + 2);
?>
<div class="table-footer">
    <p>عرض <strong><?= (int) $pagination['from'] ?></strong>–<strong><?= (int) $pagination['to'] ?></strong> من <strong><?= (int) $pagination['total'] ?></strong></p>
    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="صفحات النتائج">
            <?php if ($page > 1): ?>
                <a class="page-arrow" href="<?= Ui::e(Ui::url('', array_merge($baseParams, ['page' => $page - 1]))) ?>" aria-label="الصفحة السابقة"><?= Ui::icon('chevron-right', 17) ?></a>
            <?php else: ?><span class="page-arrow is-disabled"><?= Ui::icon('chevron-right', 17) ?></span><?php endif; ?>

            <?php if ($start > 1): ?>
                <a href="<?= Ui::e(Ui::url('', array_merge($baseParams, ['page' => 1]))) ?>">1</a>
                <?php if ($start > 2): ?><span class="page-dots">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="<?= Ui::e(Ui::url('', array_merge($baseParams, ['page' => $i]))) ?>" class="<?= $i === $page ? 'is-current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($end < $pages): ?>
                <?php if ($end < $pages - 1): ?><span class="page-dots">…</span><?php endif; ?>
                <a href="<?= Ui::e(Ui::url('', array_merge($baseParams, ['page' => $pages]))) ?>"><?= $pages ?></a>
            <?php endif; ?>

            <?php if ($page < $pages): ?>
                <a class="page-arrow" href="<?= Ui::e(Ui::url('', array_merge($baseParams, ['page' => $page + 1]))) ?>" aria-label="الصفحة التالية"><?= Ui::icon('chevron-left', 17) ?></a>
            <?php else: ?><span class="page-arrow is-disabled"><?= Ui::icon('chevron-left', 17) ?></span><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
