<?php
declare(strict_types=1);

const SERIES_TYPES = ['TV', 'ONA', 'Movie', 'Special', 'OVA'];
const SERIES_STATUSES = ['Ongoing', 'Completed', 'Upcoming'];
const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['config'][$key] ?? $default;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Builds a public URL for a route like "series/foo" with optional query params. */
function url(string $route = '', array $query = []): string
{
    $route = trim($route, '/');
    if (config('pretty_urls')) {
        $u = BASE_PATH . '/' . $route;
        return $query ? $u . '?' . http_build_query($query) : $u;
    }
    $params = $route === '' ? $query : ['r' => $route] + $query;
    return BASE_PATH . '/index.php' . ($params ? '?' . http_build_query($params) : '');
}

/** Builds a URL with the current query string merged with $changes (null removes a key). */
function url_with(string $route, array $changes): string
{
    $q = $_GET;
    unset($q['r']);
    foreach ($changes as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return url($route, $q);
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 0;
    return BASE_PATH . '/assets/' . $path . '?v=' . $v;
}

function admin_url(string $page = 'index.php', array $query = []): string
{
    return BASE_PATH . '/admin/' . $page . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . bin2hex(random_bytes(3));
}

function format_ep(float|int|string $n): string
{
    $f = (float) $n;
    return floor($f) == $f ? (string) (int) $f : rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
}

function series_url(array $s): string
{
    return url('series/' . $s['slug']);
}

function episode_url(string $seriesSlug, float|int|string $number): string
{
    return url('watch/' . $seriesSlug . '-episode-' . format_ep($number));
}

function poster_url(array $s): string
{
    if (!empty($s['poster'])) {
        return media_url($s['poster']);
    }
    return url('img/poster/' . (int) $s['id'] . '.svg');
}

function banner_url(array $s): string
{
    if (!empty($s['banner'])) {
        return media_url($s['banner']);
    }
    if (!empty($s['poster'])) {
        return media_url($s['poster']);
    }
    return url('img/banner/' . (int) $s['id'] . '.svg');
}

/** Media may be a full URL or a file in uploads/. */
function media_url(string $path): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return BASE_PATH . '/uploads/' . rawurlencode(basename($path));
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'just now';
    }
    $units = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
    foreach ($units as $secs => $name) {
        if ($diff >= $secs) {
            $n = intdiv($diff, $secs);
            return $n . ' ' . $name . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

function excerpt(string $text, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    return mb_strlen($text) > $len ? rtrim(mb_substr($text, 0, $len)) . '…' : $text;
}

function format_views(int $n): string
{
    if ($n >= 1000000) {
        return round($n / 1000000, 1) . 'M';
    }
    if ($n >= 1000) {
        return round($n / 1000, 1) . 'K';
    }
    return (string) $n;
}

function is_safe_url(string $url): bool
{
    return (bool) preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/* ---------- Flash, CSRF, auth ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request token. Go back and try again.');
    }
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $st = db()->prepare('SELECT id, username FROM users WHERE id = ?');
    $st->execute([$_SESSION['admin_id']]);
    return $st->fetch() ?: null;
}

function require_admin(): array
{
    $user = current_admin();
    if (!$user) {
        redirect(admin_url('login.php'));
    }
    return $user;
}

/* ---------- Data access ---------- */

function all_genres(): array
{
    static $cache = null;
    return $cache ??= db()->query('SELECT g.*, (SELECT COUNT(*) FROM series_genres sg WHERE sg.genre_id = g.id) AS total
        FROM genres g ORDER BY g.name')->fetchAll();
}

function series_genres(int $seriesId): array
{
    $st = db()->prepare('SELECT g.* FROM genres g JOIN series_genres sg ON sg.genre_id = g.id WHERE sg.series_id = ? ORDER BY g.name');
    $st->execute([$seriesId]);
    return $st->fetchAll();
}

/** Columns used by series cards, including latest episode number. */
function series_card_sql(): string
{
    return 's.*, (SELECT MAX(number) FROM episodes e WHERE e.series_id = s.id) AS latest_ep,
        (SELECT COUNT(*) FROM episodes e WHERE e.series_id = s.id) AS ep_count';
}

function popular_series(int $limit = 10): array
{
    $st = db()->prepare('SELECT ' . series_card_sql() . ' FROM series s ORDER BY s.views DESC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function paginate(int $total, int $page, int $perPage): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'limit' => $perPage, 'total' => $total];
}

/* ---------- Rendering ---------- */

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require ROOT . '/pages/' . $view . '.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require ROOT . '/includes/partials/' . $name . '.php';
}

function not_found(): never
{
    http_response_code(404);
    render('404', ['pageTitle' => 'Page not found']);
    exit;
}

/** Wraps a URL for safe use inside an inline CSS url(). */
function css_url(string $url): string
{
    return "url('" . str_replace(["'", '\\', '"', "\n", "\r", ')'], ['%27', '%5C', '%22', '', '', '%29'], $url) . "')";
}
