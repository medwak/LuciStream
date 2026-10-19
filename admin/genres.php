<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $name = trim((string) ($_POST['name'] ?? ''));
    if (($action === 'add' || $action === 'rename') && $name === '') {
        flash('error', 'Genre name is required.');
    } elseif ($action === 'add') {
        $slug = slugify($name);
        $st = db()->prepare('SELECT COUNT(*) FROM genres WHERE slug = ?');
        $st->execute([$slug]);
        if ($st->fetchColumn() > 0) {
            flash('error', 'Genre “' . $name . '” already exists.');
        } else {
            db()->prepare('INSERT INTO genres (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
            flash('success', 'Genre “' . $name . '” added.');
        }
    } elseif ($action === 'rename') {
        db()->prepare('UPDATE genres SET name = ? WHERE id = ?')->execute([$name, (int) $_POST['id']]);
        flash('success', 'Genre renamed.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM genres WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Genre deleted.');
    }
    redirect(admin_url('genres.php'));
}

admin_header('Genres', $user);
?>
<div class="admin-head"><h1>Genres</h1></div>
<div class="panel">
    <form method="post" class="form row">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <label class="grow">New genre <input name="name" required placeholder="e.g. Xianxia"></label>
        <div class="form-actions"><button class="btn btn-primary">Add genre</button></div>
    </form>
</div>
<div class="panel">
    <table class="table">
        <thead><tr><th>Name</th><th>Slug</th><th>Series</th><th></th></tr></thead>
        <tbody>
        <?php foreach (all_genres() as $g): ?>
            <tr>
                <td>
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="rename"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <input name="name" value="<?= e($g['name']) ?>" aria-label="Genre name"> <button class="btn btn-small btn-ghost">Rename</button>
                    </form>
                </td>
                <td class="muted"><?= e($g['slug']) ?></td>
                <td><?= (int) $g['total'] ?></td>
                <td class="right">
                    <form method="post" class="inline-form" data-confirm="Delete genre “<?= e($g['name']) ?>”? Series keep their other genres.">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <button class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
