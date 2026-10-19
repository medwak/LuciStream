<?php
// Minimal JSON API used by the live search box.
header('Content-Type: application/json; charset=utf-8');
if ($arg !== 'search') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}
$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
    echo '[]';
    exit;
}
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
$st = db()->prepare('SELECT ' . series_card_sql() . " FROM series s WHERE s.title LIKE ? ESCAPE '\\' OR s.alt_title LIKE ? ESCAPE '\\'
    ORDER BY s.views DESC LIMIT 8");
$st->execute([$like, $like]);
echo json_encode(array_map(fn($s) => [
    'title'  => $s['title'],
    'url'    => series_url($s),
    'poster' => poster_url($s),
    'meta'   => $s['type'] . ' · ' . $s['status'] . ($s['latest_ep'] ? ' · EP ' . format_ep($s['latest_ep']) : ''),
], $st->fetchAll()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
