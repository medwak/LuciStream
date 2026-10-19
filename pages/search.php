<?php
$q = trim((string) ($_GET['q'] ?? ''));
$results = [];
$pager = paginate(0, 1, (int) config('per_page'));
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where = "WHERE s.title LIKE ? ESCAPE '\\' OR s.alt_title LIKE ? ESCAPE '\\' OR s.studio LIKE ? ESCAPE '\\'";
    $st = db()->prepare("SELECT COUNT(*) FROM series s $where");
    $st->execute([$like, $like, $like]);
    $pager = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1), (int) config('per_page'));
    $st = db()->prepare('SELECT ' . series_card_sql() . " FROM series s $where ORDER BY s.views DESC LIMIT ? OFFSET ?");
    $st->execute([$like, $like, $like, $pager['limit'], $pager['offset']]);
    $results = $st->fetchAll();
}
partial('header', ['section' => 'search', 'pageTitle' => $q !== '' ? 'Search: ' . $q : 'Search']);
?>
<div class="container">
    <div class="page-head">
        <h1><?= $q !== '' ? 'Results for “' . e($q) . '”' : 'Search' ?></h1>
        <?php if ($q !== ''): ?><p class="muted"><?= (int) $pager['total'] ?> series found</p><?php endif; ?>
    </div>
    <?php if ($results): ?>
        <div class="grid grid-wide">
            <?php foreach ($results as $s): ?><?php partial('card', ['s' => $s]); ?><?php endforeach; ?>
        </div>
        <?php partial('pagination', ['pager' => $pager, 'route' => 'search']); ?>
    <?php else: ?>
        <p class="empty"><?= $q === '' ? 'Type a title in the search box above.' : 'No series matched your search.' ?></p>
    <?php endif; ?>
</div>
<?php partial('footer'); ?>
