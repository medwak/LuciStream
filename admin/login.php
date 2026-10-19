<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';

if (current_admin()) {
    redirect(admin_url());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Simple brute-force throttle per session.
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] > 5 && time() - ($_SESSION['login_last'] ?? 0) < 60) {
        $error = 'Too many attempts. Wait a minute and try again.';
    } else {
        $_SESSION['login_last'] = time();
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([trim((string) ($_POST['username'] ?? ''))]);
        $user = $st->fetch();
        if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $user['id'];
            unset($_SESSION['login_attempts'], $_SESSION['login_last']);
            redirect(admin_url());
        }
        $error = 'Wrong username or password.';
    }
}

admin_header('Log in', null);
?>
<div class="login-box">
    <a href="<?= url() ?>" class="logo"><span class="logo-mark">L</span><span class="logo-text"><?= e(config('site_name')) ?></span></a>
    <h1>Admin login</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Username <input name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></label>
        <label>Password <input type="password" name="password" required></label>
        <button class="btn btn-primary">Log in</button>
    </form>
</div>
<?php admin_footer(); ?>
