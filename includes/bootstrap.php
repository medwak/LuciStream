<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$GLOBALS['config'] = require ROOT . '/config.php';
date_default_timezone_set($GLOBALS['config']['timezone']);

// Base path of the app relative to the web root, e.g. "" or "/lucistream".
define('BASE_PATH', detect_base_path());

function detect_base_path(): string
{
    $configured = $GLOBALS['config']['base_path'] ?? null;
    if ($configured !== null) {
        return rtrim((string) $configured, '/');
    }
    $docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''), '/');
    $root = str_replace('\\', '/', realpath(ROOT) ?: ROOT);
    if ($docRoot !== '' && str_starts_with($root . '/', $docRoot . '/')) {
        return rtrim(substr($root, strlen($docRoot)), '/');
    }
    // Fallback: derive from the executing script (index.php or admin/*.php).
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (basename($dir) === 'admin') {
        $dir = dirname($dir);
    }
    return rtrim($dir, '/');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('lucistream');
    session_start();
}

require ROOT . '/includes/db.php';
require ROOT . '/includes/functions.php';
