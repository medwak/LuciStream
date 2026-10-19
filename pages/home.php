<?php
$featured = db()->query('SELECT ' . series_card_sql() . ' FROM series s WHERE s.featured = 1 ORDER BY s.updated_at DESC LIMIT 6')->fetchAll();
if (!$featured) {
    $featured = popular_series(5);
}

$latest = db()->query('SELECT ' . series_card_sql() . ', e.number AS ep_number, e.created_at AS ep_created
    FROM episodes e JOIN series s ON s.id = e.series_id
    ORDER BY e.created_at DESC, e.id DESC LIMIT 18')->fetchAll();

$ongoing = db()->query('SELECT ' . series_card_sql() . " FROM series s WHERE s.status = 'Ongoing' ORDER BY s.updated_at DESC LIMIT 12")->fetchAll();
$completed = db()->query('SELECT ' . series_card_sql() . " FROM series s WHERE s.status = 'Completed' ORDER BY s.updated_at DESC LIMIT 12")->fetchAll();

partial('header', ['section' => '']);
?>
<?php if ($featured): ?>
<section class="hero" data-slider>
    <?php foreach ($featured as $i => $f): ?>
        <div class="hero-slide<?= $i === 0 ? ' active' : '' ?>" style="--art: <?= e(css_url(banner_url($f))) ?>">
            <div class="container hero-content">
                <span class="hero-spot">#<?= $i + 1 ?> Spotlight</span>
                <h1 class="hero-title"><?= e($f['title']) ?></h1>
                <div class="hero-meta">
                    <span class="pill"><?= e($f['type']) ?></span>
                    <span class="pill"><?= e($f['status']) ?></span>
                    <?php if ($f['release_year']): ?><span><?= (int) $f['release_year'] ?></span><?php endif; ?>
                    <?php if ((float) $f['rating'] > 0): ?><span>★ <?= e(number_format((float) $f['rating'], 1)) ?></span><?php endif; ?>
                    <?php if ($f['latest_ep']): ?><span>EP <?= e(format_ep($f['latest_ep'])) ?></span><?php endif; ?>
                </div>
                <p class="hero-synopsis"><?= e(excerpt($f['synopsis'], 220)) ?></p>
                <div class="hero-actions">
                    <?php if ($f['latest_ep']): ?>
                        <a class="btn btn-primary" href="<?= e(episode_url($f['slug'], $f['latest_ep'])) ?>">▶ Watch Now</a>
                    <?php endif; ?>
                    <a class="btn btn-ghost" href="<?= e(series_url($f)) ?>">Details ›</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (count($featured) > 1): ?>
        <div class="hero-dots">
            <?php foreach ($featured as $i => $f): ?>
                <button class="<?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<div class="container">
    <section class="section" id="continue-watching" hidden>
        <div class="section-head"><h2>Continue Watching</h2><button class="link-btn" data-clear-history>Clear</button></div>
        <div class="row-scroll" data-history-list></div>
    </section>

    <div class="layout">
        <div class="content">
            <section class="section">
                <div class="section-head">
                    <h2>Latest Episodes</h2>
                    <a href="<?= url('browse', ['order' => 'update']) ?>" class="more">View all ›</a>
                </div>
                <div class="grid">
                    <?php foreach ($latest as $s): ?>
                        <?php partial('card', ['s' => $s, 'ep' => $s['ep_number'], 'epTime' => $s['ep_created']]); ?>
                    <?php endforeach; ?>
                </div>
                <?php if (!$latest): ?><p class="empty">No episodes yet. Add some from the admin panel.</p><?php endif; ?>
            </section>

            <?php foreach (['Ongoing Series' => [$ongoing, 'Ongoing'], 'Completed Series' => [$completed, 'Completed']] as $heading => [$list, $status]): ?>
                <?php if (!$list) continue; ?>
                <section class="section">
                    <div class="section-head">
                        <h2><?= e($heading) ?></h2>
                        <a href="<?= url('browse', ['status' => $status]) ?>" class="more">View all ›</a>
                    </div>
                    <div class="row-scroll">
                        <?php foreach ($list as $s): ?>
                            <?php partial('card', ['s' => $s]); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <?php partial('sidebar'); ?>
    </div>
</div>
<?php partial('footer'); ?>
