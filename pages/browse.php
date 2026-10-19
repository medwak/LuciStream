<?php
$where = [];
$params = [];
$genre = null;

if (!empty($_GET['genre'])) {
    $st = db()->prepare('SELECT * FROM genres WHERE slug = ?');
    $st->execute([(string) $_GET['genre']]);
    $genre = $st->fetch();
    if (!$genre) {
        not_found();
    }
    $where[] = 's.id IN (SELECT series_id FROM series_genres WHERE genre_id = ?)';
    $params[] = $genre['id'];
}
$status = in_array($_GET['status'] ?? '', SERIES_STATUSES, true) ? $_GET['status'] : '';
if ($status) {
    $where[] = 's.status = ?';
    $params[] = $status;
}
$type = in_array($_GET['type'] ?? '', SERIES_TYPES, true) ? $_GET['type'] : '';
if ($type) {
    $where[] = 's.type = ?';
    $params[] = $type;
}
$year = (int) ($_GET['year'] ?? 0);
if ($year > 0) {
    $where[] = 's.release_year = ?';
    $params[] = $year;
}
$orders = [
    'update'  => ['Latest Update', 's.updated_at DESC'],
    'added'   => ['Recently Added', 's.created_at DESC'],
    'popular' => ['Most Popular', 's.views DESC'],
    'rating'  => ['Top Rated', 's.rating DESC'],
    'title'   => ['Title A-Z', 's.title COLLATE NOCASE ASC'],
];
$order = array_key_exists($_GET['order'] ?? '', $orders) ? $_GET['order'] : 'update';

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$st = db()->prepare("SELECT COUNT(*) FROM series s $whereSql");
$st->execute($params);
$pager = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1), (int) config('per_page'));

$st = db()->prepare('SELECT ' . series_card_sql() . " FROM series s $whereSql ORDER BY {$orders[$order][1]} LIMIT ? OFFSET ?");
$st->execute([...$params, $pager['limit'], $pager['offset']]);
$results = $st->fetchAll();

$years = db()->query('SELECT DISTINCT release_year FROM series WHERE release_year IS NOT NULL ORDER BY release_year DESC')->fetchAll(PDO::FETCH_COLUMN);
$route = $genre ? 'genre/' . $genre['slug'] : 'browse';
$heading = $genre ? $genre['name'] . ' Donghua' : 'Browse Donghua';

partial('header', ['section' => $genre ? 'genre' : 'browse', 'pageTitle' => $heading]);
?>
<div class="container">
    <div class="page-head">
        <h1><?= e($heading) ?></h1>
        <p class="muted"><?= (int) $pager['total'] ?> series found</p>
    </div>

    <form class="filters" method="get" action="<?= url('browse') ?>">
        <?php if (!config('pretty_urls')): ?><input type="hidden" name="r" value="browse"><?php endif; ?>
        <select name="genre" aria-label="Genre">
            <option value="">All Genres</option>
            <?php foreach (all_genres() as $g): ?>
                <option value="<?= e($g['slug']) ?>" <?= $genre && $genre['id'] == $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">All Status</option>
            <?php foreach (SERIES_STATUSES as $v): ?><option <?= $status === $v ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
        </select>
        <select name="type" aria-label="Type">
            <option value="">All Types</option>
            <?php foreach (SERIES_TYPES as $v): ?><option <?= $type === $v ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
        </select>
        <select name="year" aria-label="Year">
            <option value="">All Years</option>
            <?php foreach ($years as $y): ?><option <?= $year === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option><?php endforeach; ?>
        </select>
        <select name="order" aria-label="Sort">
            <?php foreach ($orders as $k => [$label]): ?><option value="<?= e($k) ?>" <?= $order === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>

    <?php if ($results): ?>
        <div class="grid grid-wide">
            <?php foreach ($results as $s): ?><?php partial('card', ['s' => $s]); ?><?php endforeach; ?>
        </div>
        <?php partial('pagination', ['pager' => $pager, 'route' => $route]); ?>
    <?php else: ?>
        <p class="empty">Nothing matches those filters.</p>
    <?php endif; ?>
</div>
<?php partial('footer'); ?>
