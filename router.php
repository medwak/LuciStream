<?php
// Router for PHP's built-in server: php -S localhost:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    // Never serve the database or config through the dev server.
    if (preg_match('#^/(data|includes|pages)/|^/config\.php$#', $path)) {
        http_response_code(403);
        exit('Forbidden');
    }
    return false;
}
if (str_starts_with($path, '/admin/') || $path === '/admin') {
    return false;
}
require __DIR__ . '/index.php';
