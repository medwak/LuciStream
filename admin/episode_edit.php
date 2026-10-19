<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';
require __DIR__ . '/_episode.php';
$user = require_admin();

$st = db()->prepare('SELECT e.*, s.title AS series_title, s.slug FROM episodes e JOIN series s ON s.id = e.series_id WHERE e.id = ?');
$st->execute([(int) ($_GET['id'] ?? 0)]);
$ep = $st->fetch() ?: redirect(admin_url('series.php'));

$st = db()->prepare('SELECT name, kind, url FROM servers WHERE episode_id = ? ORDER BY sort, id');
$st->execute([$ep['id']]);
$servers = $st->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $number = (float) ($_POST['number'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    [$servers, $errors] = posted_servers();
    if ($number <= 0) {
        $errors[] = 'Episode number must be greater than 0.';
    }
    $st = db()->prepare('SELECT COUNT(*) FROM episodes WHERE series_id = ? AND number = ? AND id <> ?');
    $st->execute([$ep['series_id'], $number, $ep['id']]);
    if ($st->fetchColumn() > 0) {
        $errors[] = 'Episode ' . format_ep($number) . ' already exists in this series.';
    }
    if (!$errors) {
        db()->beginTransaction();
        db()->prepare('UPDATE episodes SET number = ?, title = ? WHERE id = ?')->execute([$number, $title, $ep['id']]);
        save_servers((int) $ep['id'], $servers);
        db()->commit();
        flash('success', 'Episode ' . format_ep($number) . ' saved.');
        redirect(admin_url('episodes.php', ['series_id' => $ep['series_id']]));
    }
    $ep['number'] = $number;
    $ep['title'] = $title;
}

admin_header('Edit episode', $user);
?>
<div class="admin-head">
    <h1><?= e($ep['series_title']) ?> <small class="muted">EP <?= e(format_ep($ep['number'])) ?></small></h1>
    <div class="actions">
        <a class="btn btn-ghost" href="<?= admin_url('episodes.php', ['series_id' => $ep['series_id']]) ?>">‹ Back to episodes</a>
        <a class="btn btn-ghost" target="_blank" rel="noopener" href="<?= e(episode_url($ep['slug'], $ep['number'])) ?>">View ↗</a>
    </div>
</div>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<div class="panel">
    <form method="post" class="form">
        <?= csrf_field() ?>
        <div class="row">
            <label>Episode number * <input type="number" step="0.5" min="0" name="number" value="<?= e(format_ep($ep['number'])) ?>" required></label>
            <label class="grow">Title <input name="title" value="<?= e($ep['title']) ?>"></label>
        </div>
        <h3>Video sources</h3>
        <p class="muted small">“Embed” loads the URL in an iframe (most video hosts). “Direct video” plays an .mp4 or .webm file in the browser's own player.</p>
        <?php server_rows($servers); ?>
        <div class="form-actions">
            <button class="btn btn-primary">Save episode</button>
            <a class="btn btn-ghost" href="<?= admin_url('episodes.php', ['series_id' => $ep['series_id']]) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php admin_footer(); ?>
