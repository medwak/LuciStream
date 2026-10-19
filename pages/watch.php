<?php
if (!preg_match('/^(.+)-episode-(\d+(?:\.\d+)?)$/', $arg, $m)) {
    not_found();
}
[$_, $slug, $number] = $m;

$st = db()->prepare('SELECT * FROM series WHERE slug = ?');
$st->execute([$slug]);
$s = $st->fetch();
if (!$s) {
    not_found();
}
$st = db()->prepare('SELECT * FROM episodes WHERE series_id = ? AND number = ?');
$st->execute([$s['id'], (float) $number]);
$ep = $st->fetch();
if (!$ep) {
    not_found();
}

// Count one view per episode per session.
$seen = $_SESSION['seen'] ?? [];
if (!in_array((int) $ep['id'], $seen, true)) {
    db()->prepare('UPDATE episodes SET views = views + 1 WHERE id = ?')->execute([$ep['id']]);
    db()->prepare('UPDATE series SET views = views + 1 WHERE id = ?')->execute([$s['id']]);
    $_SESSION['seen'] = array_slice([...$seen, (int) $ep['id']], -200);
}

$st = db()->prepare('SELECT * FROM servers WHERE episode_id = ? ORDER BY sort, id');
$st->execute([$ep['id']]);
$servers = array_values(array_filter($st->fetchAll(), fn($srv) => is_safe_url($srv['url'])));

$st = db()->prepare('SELECT id, number, title FROM episodes WHERE series_id = ? ORDER BY number ASC');
$st->execute([$s['id']]);
$episodes = $st->fetchAll();
$prev = $next = null;
foreach ($episodes as $i => $row) {
    if ((int) $row['id'] === (int) $ep['id']) {
        $prev = $episodes[$i - 1] ?? null;
        $next = $episodes[$i + 1] ?? null;
        break;
    }
}

$epLabel = 'Episode ' . format_ep($ep['number']) . ($ep['title'] ? ': ' . $ep['title'] : '');
$history = json_encode([
    'slug' => $s['slug'], 'title' => $s['title'], 'poster' => poster_url($s),
    'ep' => format_ep($ep['number']), 'url' => episode_url($s['slug'], $ep['number']),
], JSON_UNESCAPED_SLASHES);

partial('header', [
    'section' => 'watch',
    'pageTitle' => $s['title'] . ' ' . $epLabel,
    'metaDescription' => 'Watch ' . $s['title'] . ' ' . $epLabel . ' online in HD. ' . excerpt($s['synopsis'], 100),
    'ogImage' => poster_url($s),
]);
?>
<div class="container watch-page" data-history='<?= e($history) ?>'>
    <nav class="breadcrumb">
        <a href="<?= url() ?>">Home</a> / <a href="<?= e(series_url($s)) ?>"><?= e($s['title']) ?></a> / <span>Episode <?= e(format_ep($ep['number'])) ?></span>
    </nav>

    <div class="watch-layout">
        <div class="watch-main">
            <div class="player" data-player>
                <?php if (!$servers): ?>
                    <div class="player-empty">No video sources are available for this episode yet.</div>
                <?php else: ?>
                    <?php $srv = $servers[0]; ?>
                    <?php if ($srv['kind'] === 'video'): ?>
                        <video controls playsinline preload="metadata" poster="<?= e(banner_url($s)) ?>" src="<?= e($srv['url']) ?>"></video>
                    <?php else: ?>
                        <iframe src="<?= e($srv['url']) ?>" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture; fullscreen" referrerpolicy="no-referrer-when-downgrade" title="Video player"></iframe>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="player-bar">
                <div class="servers">
                    <span class="muted">Servers:</span>
                    <?php foreach ($servers as $i => $srv): ?>
                        <button class="server-btn<?= $i === 0 ? ' active' : '' ?>" data-server-kind="<?= e($srv['kind']) ?>" data-server-url="<?= e($srv['url']) ?>"><?= e($srv['name']) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="ep-nav">
                    <?php if ($prev): ?><a class="btn btn-small btn-secondary" href="<?= e(episode_url($s['slug'], $prev['number'])) ?>">‹ Prev</a><?php endif; ?>
                    <a class="btn btn-small btn-ghost" href="<?= e(series_url($s)) ?>">All Episodes</a>
                    <?php if ($next): ?><a class="btn btn-small btn-primary" href="<?= e(episode_url($s['slug'], $next['number'])) ?>">Next ›</a><?php endif; ?>
                </div>
            </div>

            <div class="watch-info">
                <img src="<?= e(poster_url($s)) ?>" alt="" class="watch-poster">
                <div>
                    <h1><?= e($s['title']) ?> <span class="accent"><?= e($epLabel) ?></span></h1>
                    <div class="hero-meta">
                        <span class="pill"><?= e($s['type']) ?></span>
                        <span class="pill"><?= e($s['status']) ?></span>
                        <span>Released <?= e(time_ago($ep['created_at'])) ?></span>
                        <span><?= e(format_views((int) $ep['views'])) ?> views</span>
                    </div>
                    <p class="muted"><?= e(excerpt($s['synopsis'], 300)) ?></p>
                    <?php if ($next === null && $s['status'] === 'Ongoing' && $s['release_day']): ?>
                        <p class="notice">Next episode expected on <strong><?= e($s['release_day']) ?></strong>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <aside class="watch-side">
            <div class="widget">
                <div class="section-head">
                    <h2 class="widget-title">Episodes (<?= count($episodes) ?>)</h2>
                    <?php if (count($episodes) > 12): ?><input type="search" class="ep-filter" placeholder="#" data-ep-filter aria-label="Filter episodes"><?php endif; ?>
                </div>
                <div class="ep-list" data-ep-list>
                    <?php foreach ($episodes as $row): ?>
                        <a href="<?= e(episode_url($s['slug'], $row['number'])) ?>" data-ep="<?= e(format_ep($row['number'])) ?>"
                           class="<?= (int) $row['id'] === (int) $ep['id'] ? 'current' : '' ?>">
                            <span>EP <?= e(format_ep($row['number'])) ?></span>
                            <?php if ($row['title']): ?><small><?= e($row['title']) ?></small><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>
<?php partial('footer'); ?>
