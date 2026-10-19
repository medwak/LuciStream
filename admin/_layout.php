<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

function admin_header(string $title, ?array $user): void
{
    $self = basename($_SERVER['SCRIPT_NAME']);
    $nav = ['index.php' => 'Dashboard', 'series.php' => 'Series', 'genres.php' => 'Genres', 'account.php' => 'Account'];
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> - <?= e(config('site_name')) ?> Admin</title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<?php if ($user): ?>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= admin_url() ?>" class="logo"><span class="logo-mark">L</span><span class="logo-text"><?= e(config('site_name')) ?> Admin</span></a>
        <nav class="admin-nav">
            <?php foreach ($nav as $file => $label): ?>
                <a href="<?= admin_url($file) ?>" class="<?= $self === $file || ($file === 'series.php' && str_starts_with($self, 'episode')) || ($file === 'series.php' && $self === 'series_edit.php') ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-user">
            <a href="<?= url() ?>" target="_blank" rel="noopener" class="btn btn-small btn-ghost">View site ↗</a>
            <form method="post" action="<?= admin_url('logout.php') ?>"><?= csrf_field() ?><button class="btn btn-small btn-secondary">Log out</button></form>
        </div>
    </div>
</header>
<?php endif; ?>
<main class="container admin-main">
    <?php foreach (take_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
<?php
}

function admin_footer(): void
{
    ?>
</main>
<script src="<?= asset('js/admin.js') ?>"></script>
</body>
</html>
<?php
}

/** Saves an uploaded image and returns its file name, or null if none was sent. */
function save_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . ').');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Images must be 5 MB or smaller.');
    }
    $info = @getimagesize($file['tmp_name']);
    $ext = match ($info['mime'] ?? '') {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.'),
    };
    $dir = config('upload_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = $field . '-' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save the uploaded file. Check that uploads/ is writable.');
    }
    return $name;
}

function delete_upload(?string $name): void
{
    if ($name && !preg_match('#^https?://#i', $name)) {
        $path = config('upload_dir') . '/' . basename($name);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
