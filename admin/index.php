<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';
$user = require_admin();

$stats = [
    'Series'   => (int) db()->query('SELECT COUNT(*) FROM series')->fetchColumn(),
    'Episodes' => (int) db()->query('SELECT COUNT(*) FROM episodes')->fetchColumn(),
    'Genres'   => (int) db()->query('SELECT COUNT(*) FROM genres')->fetchColumn(),
    'Total views' => (int) db()->query('SELECT COALESCE(SUM(views), 0) FROM series')->fetchColumn(),
];
$recent = db()->query('SELECT e.*, s.title AS series_title, s.slug FROM episodes e JOIN series s ON s.id = e.series_id
    ORDER BY e.created_at DESC, e.id DESC LIMIT 10')->fetchAll();
$defaultPass = password_verify(config('admin_pass'), (string) db()->query('SELECT password_hash FROM users WHERE id = ' . (int) $user['id'])->fetchColumn());

admin_header('Dashboard', $user);
?>
<div class="admin-head">
    <h1>Welcome, <?= e($user['username']) ?></h1>
    <div class="actions">
        <a class="btn btn-primary" href="<?= admin_url('series_edit.php') ?>">+ New series</a>
    </div>
</div>
<?php if ($defaultPass): ?>
    <div class="alert alert-error">You are still using the default password. <a href="<?= admin_url('account.php') ?>"><u>Change it now</u></a>.</div>
<?php endif; ?>
<div class="stats">
    <?php foreach ($stats as $label => $n): ?>
        <div class="stat"><strong><?= e(format_views($n)) ?></strong><span><?= e($label) ?></span></div>
    <?php endforeach; ?>
</div>

<div class="panel">
    <h2>Recently added episodes</h2>
    <table class="table">
        <thead><tr><th>Series</th><th>Episode</th><th>Added</th><th>Views</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><a href="<?= admin_url('episodes.php', ['series_id' => $r['series_id']]) ?>"><?= e($r['series_title']) ?></a></td>
                <td>EP <?= e(format_ep($r['number'])) ?><?= $r['title'] ? ' – ' . e($r['title']) : '' ?></td>
                <td><?= e(time_ago($r['created_at'])) ?></td>
                <td><?= (int) $r['views'] ?></td>
                <td class="right">
                    <a class="btn btn-small btn-ghost" href="<?= admin_url('episode_edit.php', ['id' => $r['id']]) ?>">Edit</a>
                    <a class="btn btn-small btn-ghost" target="_blank" rel="noopener" href="<?= e(episode_url($r['slug'], $r['number'])) ?>">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
