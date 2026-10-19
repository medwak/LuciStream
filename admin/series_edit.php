<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';
$user = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$s = [
    'id' => 0, 'title' => '', 'slug' => '', 'alt_title' => '', 'synopsis' => '', 'poster' => '', 'banner' => '',
    'type' => 'ONA', 'status' => 'Ongoing', 'release_year' => date('Y'), 'studio' => '', 'rating' => '',
    'release_day' => '', 'featured' => 0,
];
$selected = [];
if ($id) {
    $st = db()->prepare('SELECT * FROM series WHERE id = ?');
    $st->execute([$id]);
    $s = $st->fetch() ?: redirect(admin_url('series.php'));
    $selected = array_column(series_genres($id), 'id');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $in = [
        'title'        => trim((string) ($_POST['title'] ?? '')),
        'slug'         => trim((string) ($_POST['slug'] ?? '')),
        'alt_title'    => trim((string) ($_POST['alt_title'] ?? '')),
        'synopsis'     => trim((string) ($_POST['synopsis'] ?? '')),
        'type'         => in_array($_POST['type'] ?? '', SERIES_TYPES, true) ? $_POST['type'] : 'ONA',
        'status'       => in_array($_POST['status'] ?? '', SERIES_STATUSES, true) ? $_POST['status'] : 'Ongoing',
        'release_year' => ($_POST['release_year'] ?? '') !== '' ? (int) $_POST['release_year'] : null,
        'studio'       => trim((string) ($_POST['studio'] ?? '')),
        'rating'       => min(10, max(0, (float) ($_POST['rating'] ?? 0))),
        'release_day'  => in_array($_POST['release_day'] ?? '', WEEKDAYS, true) ? $_POST['release_day'] : '',
        'featured'     => !empty($_POST['featured']) ? 1 : 0,
        'poster'       => $s['poster'],
        'banner'       => $s['banner'],
    ];
    $selected = array_map('intval', (array) ($_POST['genres'] ?? []));
    $in['slug'] = slugify($in['slug'] !== '' ? $in['slug'] : $in['title']);

    if ($in['title'] === '') {
        $errors[] = 'Title is required.';
    }
    $st = db()->prepare('SELECT COUNT(*) FROM series WHERE slug = ? AND id <> ?');
    $st->execute([$in['slug'], $id]);
    if ($st->fetchColumn() > 0) {
        $errors[] = 'Another series already uses the slug “' . $in['slug'] . '”.';
    }

    foreach (['poster', 'banner'] as $field) {
        try {
            if ($name = save_upload($field . '_file')) {
                delete_upload($s[$field]);
                $in[$field] = $name;
            } elseif (($u = trim((string) ($_POST[$field . '_url'] ?? ''))) !== '' && $u !== $s[$field]) {
                if (!is_safe_url($u)) {
                    throw new RuntimeException(ucfirst($field) . ' URL must start with http:// or https://');
                }
                delete_upload($s[$field]);
                $in[$field] = $u;
            } elseif (!empty($_POST[$field . '_remove'])) {
                delete_upload($s[$field]);
                $in[$field] = '';
            }
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $cols = array_keys($in);
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = :$c", $cols));
            $pdo->prepare("UPDATE series SET $set, updated_at = :updated_at WHERE id = :id")
                ->execute($in + ['updated_at' => now(), 'id' => $id]);
        } else {
            $pdo->prepare('INSERT INTO series (' . implode(', ', $cols) . ', created_at, updated_at) VALUES (:' . implode(', :', $cols) . ', :created_at, :updated_at)')
                ->execute($in + ['created_at' => now(), 'updated_at' => now()]);
            $id = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('DELETE FROM series_genres WHERE series_id = ?')->execute([$id]);
        $ins = $pdo->prepare('INSERT OR IGNORE INTO series_genres (series_id, genre_id) SELECT ?, id FROM genres WHERE id = ?');
        foreach ($selected as $gid) {
            $ins->execute([$id, $gid]);
        }
        $pdo->commit();
        flash('success', 'Saved “' . $in['title'] . '”.');
        redirect(admin_url(isset($_POST['save_episodes']) ? 'episodes.php' : 'series_edit.php', isset($_POST['save_episodes']) ? ['series_id' => $id] : ['id' => $id]));
    }
    $s = $in + $s;
}

admin_header($id ? 'Edit ' . $s['title'] : 'New series', $user);
?>
<div class="admin-head">
    <h1><?= $id ? 'Edit series' : 'New series' ?></h1>
    <div class="actions">
        <?php if ($id): ?>
            <a class="btn btn-ghost" href="<?= admin_url('episodes.php', ['series_id' => $id]) ?>">Manage episodes</a>
            <a class="btn btn-ghost" target="_blank" rel="noopener" href="<?= e(series_url($s)) ?>">View ↗</a>
        <?php endif; ?>
    </div>
</div>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="form form-grid">
    <?= csrf_field() ?>
    <div class="panel">
        <label>Title * <input name="title" required value="<?= e($s['title']) ?>" data-slug-source></label>
        <label>Slug <small class="muted">(URL part, leave empty to generate)</small><input name="slug" value="<?= e($s['slug']) ?>" data-slug-target></label>
        <label>Alternative title <input name="alt_title" value="<?= e($s['alt_title']) ?>" placeholder="e.g. Chinese / pinyin title"></label>
        <label>Synopsis <textarea name="synopsis" rows="7"><?= e($s['synopsis']) ?></textarea></label>
        <div class="row">
            <label>Type <select name="type"><?php foreach (SERIES_TYPES as $v): ?><option <?= $s['type'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
            <label>Status <select name="status"><?php foreach (SERIES_STATUSES as $v): ?><option <?= $s['status'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
            <label>Release day <select name="release_day"><option value="">—</option><?php foreach (WEEKDAYS as $v): ?><option <?= $s['release_day'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
        </div>
        <div class="row">
            <label>Year <input type="number" name="release_year" min="1950" max="2100" value="<?= e($s['release_year']) ?>"></label>
            <label>Rating (0–10) <input type="number" step="0.1" min="0" max="10" name="rating" value="<?= e($s['rating']) ?>"></label>
            <label>Studio <input name="studio" value="<?= e($s['studio']) ?>"></label>
        </div>
        <label class="check"><input type="checkbox" name="featured" value="1" <?= $s['featured'] ? 'checked' : '' ?>> Feature on the homepage slider</label>
    </div>

    <div>
        <div class="panel">
            <h2>Genres</h2>
            <div class="check-grid">
                <?php foreach (all_genres() as $g): ?>
                    <label class="check"><input type="checkbox" name="genres[]" value="<?= (int) $g['id'] ?>" <?= in_array((int) $g['id'], array_map('intval', $selected), true) ? 'checked' : '' ?>> <?= e($g['name']) ?></label>
                <?php endforeach; ?>
            </div>
            <a href="<?= admin_url('genres.php') ?>" class="small muted">Manage genres ›</a>
        </div>
        <?php foreach (['poster' => 'Poster (portrait, ~300×420)', 'banner' => 'Banner (landscape, ~1280×540)'] as $field => $label): ?>
            <div class="panel">
                <h2><?= e($label) ?></h2>
                <?php if ($s['id']): ?><img class="preview preview-<?= $field ?>" src="<?= e($field === 'poster' ? poster_url($s) : banner_url($s)) ?>" alt=""><?php endif; ?>
                <label>Upload image <input type="file" name="<?= $field ?>_file" accept="image/jpeg,image/png,image/webp,image/gif"></label>
                <label>…or image URL <input type="url" name="<?= $field ?>_url" value="<?= e(preg_match('#^https?://#', (string) $s[$field]) ? $s[$field] : '') ?>" placeholder="https://"></label>
                <?php if ($s[$field]): ?><label class="check"><input type="checkbox" name="<?= $field ?>_remove" value="1"> Remove current image</label><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary">Save</button>
        <button class="btn btn-secondary" name="save_episodes" value="1">Save &amp; manage episodes</button>
        <a class="btn btn-ghost" href="<?= admin_url('series.php') ?>">Cancel</a>
    </div>
</form>
<?php admin_footer(); ?>
