<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

// Resolve the route from a pretty URL or from ?r= when rewriting is unavailable.
if (isset($_GET['r'])) {
    $route = (string) $_GET['r'];
} else {
    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if (BASE_PATH !== '' && str_starts_with($path, BASE_PATH)) {
        $path = substr($path, strlen(BASE_PATH));
    }
    $route = preg_replace('#^/?index\.php#', '', $path) ?? '';
}
$parts = array_values(array_filter(explode('/', trim($route, '/')), 'strlen'));
$section = $parts[0] ?? '';
$arg = $parts[1] ?? '';

switch ($section) {
    case '':
        require ROOT . '/pages/home.php';
        break;
    case 'series':
        $arg !== '' ? require ROOT . '/pages/series.php' : redirect(url('browse'));
        break;
    case 'watch':
        $arg !== '' ? require ROOT . '/pages/watch.php' : not_found();
        break;
    case 'browse':
        require ROOT . '/pages/browse.php';
        break;
    case 'genre':
        $_GET['genre'] = $arg;
        require ROOT . '/pages/browse.php';
        break;
    case 'search':
        require ROOT . '/pages/search.php';
        break;
    case 'schedule':
        require ROOT . '/pages/schedule.php';
        break;
    case 'az':
        require ROOT . '/pages/az.php';
        break;
    case 'mylist':
        require ROOT . '/pages/mylist.php';
        break;
    case 'api':
        require ROOT . '/pages/api.php';
        break;
    case 'img':
        require ROOT . '/pages/img.php';
        break;
    default:
        not_found();
}
