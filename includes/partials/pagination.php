<?php
/** Expects $pager (from paginate()) and $route. */
if ($pager['pages'] <= 1) {
    return;
}
$p = $pager['page'];
$start = max(1, $p - 2);
$end = min($pager['pages'], $p + 2);
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($p > 1): ?><a href="<?= e(url_with($route, ['page' => $p - 1])) ?>">‹ Prev</a><?php endif; ?>
    <?php if ($start > 1): ?><a href="<?= e(url_with($route, ['page' => 1])) ?>">1</a><?php if ($start > 2): ?><span>…</span><?php endif; ?><?php endif; ?>
    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i === $p): ?><span class="current"><?= $i ?></span><?php else: ?><a href="<?= e(url_with($route, ['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($end < $pager['pages']): ?><?php if ($end < $pager['pages'] - 1): ?><span>…</span><?php endif; ?><a href="<?= e(url_with($route, ['page' => $pager['pages']])) ?>"><?= $pager['pages'] ?></a><?php endif; ?>
    <?php if ($p < $pager['pages']): ?><a href="<?= e(url_with($route, ['page' => $p + 1])) ?>">Next ›</a><?php endif; ?>
</nav>
