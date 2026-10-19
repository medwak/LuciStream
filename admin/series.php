<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM series WHERE id = ?');
    $st->execute([(int) $_POST['id']]);
    if ($s = $st->fetch()) {
        db()->prepare('DELETE FROM series WHERE id = ?')->execute([$s['id']]);
        delete_upload($s['poster']);
        delete_upload($s['banner']);
        flash('success', 'Deleted “' . $s['title'] . '” and all its episodes.');
    }
    redirect(admin_url('series.php'));
}

$q = trim((string) ($_GET['q'] ?? ''));
$where = $q !== '' ? 'WHERE s.title LIKE ? OR s.alt_title LIKE ?' : '';
$params = $q !== '' ? ["%$q%", "%$q%"] : [];
$st = db()->prepare("SELECT COUNT(*) FROM series s $where");
$st->execute($params);
$pager = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1), 30);
$st = db()->prepare('SELECT ' . series_card_sql() . " FROM series s $where ORDER BY s.updated_at DESC LIMIT ? OFFSET ?");
$st->execute([...$params, $pager['limit'], $pager['offset']]);
$list = $st->fetchAll();

admin_header('Series', $user);
?>
<div class="admin-head">
    <h1>Series <small class="muted">(<?= (int) $pager['total'] ?>)</small></h1>
    <div class="actions">
        <form method="get" class="inline-form"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search series…"></form>
        <a class="btn btn-primary" href="<?= admin_url('series_edit.php') ?>">+ New series</a>
    </div>
</div>
<div class="panel">
    <table class="table">
        <thead><tr><th></th><th>Title</th><th>Type</th><th>Status</th><th>Episodes</th><th>Views</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $s): ?>
            <tr>
                <td><img class="thumb" src="<?= e(poster_url($s)) ?>" alt=""></td>
                <td><strong><?= e($s['title']) ?></strong><?= $s['featured'] ? ' <span class="pill pill-accent">Featured</span>' : '' ?><br><small class="muted"><?= e($s['alt_title']) ?></small></td>
                <td><?= e($s['type']) ?></td>
                <td><?= e($s['status']) ?></td>
                <td><?= (int) $s['ep_count'] ?><?= $s['latest_ep'] ? ' <small class="muted">(latest ' . e(format_ep($s['latest_ep'])) . ')</small>' : '' ?></td>
                <td><?= e(format_views((int) $s['views'])) ?></td>
                <td><?= e(time_ago($s['updated_at'])) ?></td>
                <td class="right nowrap">
                    <a class="btn btn-small btn-primary" href="<?= admin_url('episodes.php', ['series_id' => $s['id']]) ?>">Episodes</a>
                    <a class="btn btn-small btn-ghost" href="<?= admin_url('series_edit.php', ['id' => $s['id']]) ?>">Edit</a>
                    <form method="post" class="inline-form" data-confirm="Delete “<?= e($s['title']) ?>” and all its episodes?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <button class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$list): ?><p class="empty">No series found.</p><?php endif; ?>
</div>
<?php if ($pager['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $pager['pages']; $i++): ?>
            <?php if ($i === $pager['page']): ?><span class="current"><?= $i ?></span><?php else: ?><a href="<?= admin_url('series.php', ['q' => $q, 'page' => $i]) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php admin_footer(); ?>
