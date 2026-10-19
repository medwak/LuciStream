<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';
require __DIR__ . '/_episode.php';
$user = require_admin();

$seriesId = (int) ($_GET['series_id'] ?? 0);
$st = db()->prepare('SELECT * FROM series WHERE id = ?');
$st->execute([$seriesId]);
$s = $st->fetch() ?: redirect(admin_url('series.php'));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM episodes WHERE id = ? AND series_id = ?')->execute([(int) $_POST['id'], $seriesId]);
        flash('success', 'Episode deleted.');
        redirect(admin_url('episodes.php', ['series_id' => $seriesId]));
    }
    if ($action === 'add') {
        $number = (float) ($_POST['number'] ?? 0);
        [$servers, $errors] = posted_servers();
        if ($number <= 0) {
            $errors[] = 'Episode number must be greater than 0.';
        }
        $st = db()->prepare('SELECT COUNT(*) FROM episodes WHERE series_id = ? AND number = ?');
        $st->execute([$seriesId, $number]);
        if ($st->fetchColumn() > 0) {
            $errors[] = 'Episode ' . format_ep($number) . ' already exists.';
        }
        if (!$errors) {
            db()->beginTransaction();
            db()->prepare('INSERT INTO episodes (series_id, number, title, created_at) VALUES (?, ?, ?, ?)')
                ->execute([$seriesId, $number, trim((string) ($_POST['title'] ?? '')), now()]);
            save_servers((int) db()->lastInsertId(), $servers);
            touch_series($seriesId);
            db()->commit();
            flash('success', 'Episode ' . format_ep($number) . ' added.');
            redirect(admin_url('episodes.php', ['series_id' => $seriesId]));
        }
    }
}

$st = db()->prepare('SELECT e.*, (SELECT COUNT(*) FROM servers v WHERE v.episode_id = e.id) AS server_count
    FROM episodes e WHERE e.series_id = ? ORDER BY e.number DESC');
$st->execute([$seriesId]);
$episodes = $st->fetchAll();
$nextNumber = $episodes ? floor((float) $episodes[0]['number']) + 1 : 1;

admin_header('Episodes – ' . $s['title'], $user);
?>
<div class="admin-head">
    <h1><?= e($s['title']) ?> <small class="muted">episodes (<?= count($episodes) ?>)</small></h1>
    <div class="actions">
        <a class="btn btn-ghost" href="<?= admin_url('series_edit.php', ['id' => $seriesId]) ?>">Edit series</a>
        <a class="btn btn-ghost" target="_blank" rel="noopener" href="<?= e(series_url($s)) ?>">View ↗</a>
    </div>
</div>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="panel">
    <h2>Add episode</h2>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <div class="row">
            <label>Episode number * <input type="number" step="0.5" min="0" name="number" value="<?= e($_POST['number'] ?? format_ep($nextNumber)) ?>" required></label>
            <label class="grow">Title <small class="muted">(optional)</small><input name="title" value="<?= e($_POST['title'] ?? '') ?>"></label>
        </div>
        <h3>Video sources</h3>
        <?php server_rows($errors ? array_values(posted_servers()[0]) : []); ?>
        <div class="form-actions"><button class="btn btn-primary">Add episode</button></div>
    </form>
</div>

<div class="panel">
    <h2>All episodes</h2>
    <table class="table">
        <thead><tr><th>#</th><th>Title</th><th>Servers</th><th>Views</th><th>Added</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($episodes as $ep): ?>
            <tr>
                <td><strong>EP <?= e(format_ep($ep['number'])) ?></strong></td>
                <td><?= e($ep['title']) ?></td>
                <td><?= (int) $ep['server_count'] ?: '<span class="accent">none</span>' ?></td>
                <td><?= (int) $ep['views'] ?></td>
                <td><?= e(time_ago($ep['created_at'])) ?></td>
                <td class="right nowrap">
                    <a class="btn btn-small btn-ghost" href="<?= admin_url('episode_edit.php', ['id' => $ep['id']]) ?>">Edit</a>
                    <a class="btn btn-small btn-ghost" target="_blank" rel="noopener" href="<?= e(episode_url($s['slug'], $ep['number'])) ?>">View</a>
                    <form method="post" class="inline-form" data-confirm="Delete episode <?= e(format_ep($ep['number'])) ?>?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $ep['id'] ?>">
                        <button class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$episodes): ?><p class="empty">No episodes yet.</p><?php endif; ?>
</div>
<?php admin_footer(); ?>
