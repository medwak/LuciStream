<?php
$letter = strtoupper((string) ($_GET['letter'] ?? ''));
if ($letter !== '#' && !preg_match('/^[A-Z]$/', $letter)) {
    $letter = '';
}
$where = '';
$params = [];
if ($letter === '#') {
    $where = "WHERE upper(substr(s.title, 1, 1)) NOT BETWEEN 'A' AND 'Z'";
} elseif ($letter !== '') {
    $where = 'WHERE upper(substr(s.title, 1, 1)) = ?';
    $params[] = $letter;
}
$st = db()->prepare("SELECT COUNT(*) FROM series s $where");
$st->execute($params);
$pager = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1), 60);
$st = db()->prepare('SELECT ' . series_card_sql() . " FROM series s $where ORDER BY s.title COLLATE NOCASE LIMIT ? OFFSET ?");
$st->execute([...$params, $pager['limit'], $pager['offset']]);
$list = $st->fetchAll();

partial('header', ['section' => 'az', 'pageTitle' => 'A-Z List' . ($letter ? ' - ' . $letter : '')]);
?>
<div class="container">
    <div class="page-head"><h1>A-Z Donghua List</h1></div>
    <nav class="az-bar">
        <a href="<?= url('az') ?>" class="<?= $letter === '' ? 'active' : '' ?>">All</a>
        <a href="<?= url('az', ['letter' => '#']) ?>" class="<?= $letter === '#' ? 'active' : '' ?>">#</a>
        <?php foreach (range('A', 'Z') as $l): ?>
            <a href="<?= url('az', ['letter' => $l]) ?>" class="<?= $letter === $l ? 'active' : '' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($list): ?>
        <table class="az-table">
            <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Episodes</th><th>Year</th></tr></thead>
            <tbody>
            <?php foreach ($list as $s): ?>
                <tr>
                    <td><a href="<?= e(series_url($s)) ?>" class="az-title"><img src="<?= e(poster_url($s)) ?>" alt="" loading="lazy"><?= e($s['title']) ?></a></td>
                    <td><?= e($s['type']) ?></td>
                    <td><?= e($s['status']) ?></td>
                    <td><?= (int) $s['ep_count'] ?></td>
                    <td><?= $s['release_year'] ? (int) $s['release_year'] : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php partial('pagination', ['pager' => $pager, 'route' => 'az']); ?>
    <?php else: ?>
        <p class="empty">No titles under this letter.</p>
    <?php endif; ?>
</div>
<?php partial('footer'); ?>
