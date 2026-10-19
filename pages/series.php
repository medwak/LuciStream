<?php
$st = db()->prepare('SELECT ' . series_card_sql() . ' FROM series s WHERE s.slug = ?');
$st->execute([$arg]);
$s = $st->fetch();
if (!$s) {
    not_found();
}

$genres = series_genres((int) $s['id']);
$st = db()->prepare('SELECT * FROM episodes WHERE series_id = ? ORDER BY number DESC');
$st->execute([$s['id']]);
$episodes = $st->fetchAll();
$first = $episodes ? end($episodes) : null;
$last = $episodes[0] ?? null;

$related = [];
if ($genres) {
    $ids = array_column($genres, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare('SELECT ' . series_card_sql() . ", COUNT(sg.genre_id) AS shared FROM series s
        JOIN series_genres sg ON sg.series_id = s.id AND sg.genre_id IN ($in)
        WHERE s.id <> ? GROUP BY s.id ORDER BY shared DESC, s.views DESC LIMIT 6");
    $st->execute([...$ids, $s['id']]);
    $related = $st->fetchAll();
}

$bookmark = json_encode([
    'slug' => $s['slug'], 'title' => $s['title'], 'poster' => poster_url($s), 'url' => series_url($s),
    'type' => $s['type'], 'status' => $s['status'],
], JSON_UNESCAPED_SLASHES);

partial('header', [
    'section' => 'series',
    'pageTitle' => $s['title'],
    'metaDescription' => excerpt($s['synopsis'], 155),
    'ogImage' => poster_url($s),
]);
?>
<section class="detail-hero" style="--art: <?= e(css_url(banner_url($s))) ?>">
    <div class="container detail-inner">
        <img class="detail-poster" src="<?= e(poster_url($s)) ?>" alt="<?= e($s['title']) ?>">
        <div class="detail-info">
            <nav class="breadcrumb"><a href="<?= url() ?>">Home</a> / <a href="<?= url('browse', ['type' => $s['type']]) ?>"><?= e($s['type']) ?></a> / <span><?= e($s['title']) ?></span></nav>
            <h1><?= e($s['title']) ?></h1>
            <?php if ($s['alt_title']): ?><p class="alt-title"><?= e($s['alt_title']) ?></p><?php endif; ?>
            <div class="hero-meta">
                <?php if ((float) $s['rating'] > 0): ?><span class="pill pill-accent">★ <?= e(number_format((float) $s['rating'], 1)) ?></span><?php endif; ?>
                <span class="pill"><?= e($s['type']) ?></span>
                <span class="pill"><?= e($s['status']) ?></span>
                <span><?= (int) $s['ep_count'] ?> Episodes</span>
                <span><?= e(format_views((int) $s['views'])) ?> views</span>
            </div>
            <div class="detail-actions">
                <?php if ($first): ?><a class="btn btn-primary" href="<?= e(episode_url($s['slug'], $first['number'])) ?>">▶ Episode <?= e(format_ep($first['number'])) ?></a><?php endif; ?>
                <?php if ($last && $last !== $first): ?><a class="btn btn-secondary" href="<?= e(episode_url($s['slug'], $last['number'])) ?>">Latest: EP <?= e(format_ep($last['number'])) ?></a><?php endif; ?>
                <button class="btn btn-ghost" data-bookmark='<?= e($bookmark) ?>'>+ Add to List</button>
            </div>
            <p class="synopsis"><?= nl2br(e($s['synopsis'])) ?></p>
            <dl class="facts">
                <?php if ($s['studio']): ?><div><dt>Studio</dt><dd><?= e($s['studio']) ?></dd></div><?php endif; ?>
                <?php if ($s['release_year']): ?><div><dt>Released</dt><dd><a href="<?= url('browse', ['year' => $s['release_year']]) ?>"><?= (int) $s['release_year'] ?></a></dd></div><?php endif; ?>
                <?php if ($s['release_day'] && $s['status'] === 'Ongoing'): ?><div><dt>Airs</dt><dd>Every <?= e($s['release_day']) ?></dd></div><?php endif; ?>
                <div><dt>Updated</dt><dd><?= e(time_ago($s['updated_at'])) ?></dd></div>
            </dl>
            <div class="genre-cloud">
                <?php foreach ($genres as $g): ?><a class="chip" href="<?= url('genre/' . $g['slug']) ?>"><?= e($g['name']) ?></a><?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="layout">
        <div class="content">
            <section class="section">
                <div class="section-head">
                    <h2>Episodes</h2>
                    <?php if (count($episodes) > 12): ?>
                        <input type="search" class="ep-filter" placeholder="Episode #" data-ep-filter aria-label="Filter episodes">
                    <?php endif; ?>
                </div>
                <?php if ($episodes): ?>
                    <div class="ep-grid" data-ep-list>
                        <?php foreach ($episodes as $ep): ?>
                            <a class="ep-item" href="<?= e(episode_url($s['slug'], $ep['number'])) ?>" data-ep="<?= e(format_ep($ep['number'])) ?>">
                                <strong>EP <?= e(format_ep($ep['number'])) ?></strong>
                                <small><?= e($ep['title'] ?: time_ago($ep['created_at'])) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty">No episodes released yet. Check back soon!</p>
                <?php endif; ?>
            </section>

            <?php if ($related): ?>
                <section class="section">
                    <div class="section-head"><h2>You May Also Like</h2></div>
                    <div class="grid">
                        <?php foreach ($related as $r): ?><?php partial('card', ['s' => $r]); ?><?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
        <?php partial('sidebar'); ?>
    </div>
</div>
<?php partial('footer'); ?>
