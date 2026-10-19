<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';
$user = require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    $hash = (string) $st->fetchColumn();
    $new = (string) ($_POST['new_password'] ?? '');
    $username = trim((string) ($_POST['username'] ?? ''));

    if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) {
        $errors[] = 'Current password is incorrect.';
    }
    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if ($new !== '' && strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    if ($new !== ($_POST['confirm_password'] ?? '')) {
        $errors[] = 'New passwords do not match.';
    }
    $st = db()->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
    $st->execute([$username, $user['id']]);
    if ($st->fetchColumn() > 0) {
        $errors[] = 'That username is taken.';
    }
    if (!$errors) {
        db()->prepare('UPDATE users SET username = ?, password_hash = ? WHERE id = ?')
            ->execute([$username, $new !== '' ? password_hash($new, PASSWORD_DEFAULT) : $hash, $user['id']]);
        session_regenerate_id(true);
        flash('success', 'Account updated.');
        redirect(admin_url('account.php'));
    }
}

admin_header('Account', $user);
?>
<div class="admin-head"><h1>Account</h1></div>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<div class="panel narrow">
    <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Username <input name="username" required value="<?= e($_POST['username'] ?? $user['username']) ?>"></label>
        <label>New password <small class="muted">(leave empty to keep current)</small><input type="password" name="new_password" autocomplete="new-password"></label>
        <label>Confirm new password <input type="password" name="confirm_password" autocomplete="new-password"></label>
        <label>Current password * <input type="password" name="current_password" required autocomplete="current-password"></label>
        <div class="form-actions"><button class="btn btn-primary">Save changes</button></div>
    </form>
</div>
<?php admin_footer(); ?>
