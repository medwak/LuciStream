<aside class="sidebar">
    <section class="widget">
        <h2 class="widget-title">Most Popular</h2>
        <ol class="rank-list">
            <?php foreach (popular_series(10) as $i => $p): ?>
                <li>
                    <a href="<?= e(series_url($p)) ?>">
                        <span class="rank-num"><?= $i + 1 ?></span>
                        <img src="<?= e(poster_url($p)) ?>" alt="" loading="lazy">
                        <span class="rank-info">
                            <strong><?= e($p['title']) ?></strong>
                            <small><?= e($p['type']) ?> · <?= e(format_views((int) $p['views'])) ?> views<?php if ($p['latest_ep']): ?> · EP <?= e(format_ep($p['latest_ep'])) ?><?php endif; ?></small>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
    <section class="widget">
        <h2 class="widget-title">Genres</h2>
        <div class="genre-cloud">
            <?php foreach (all_genres() as $g): ?>
                <a href="<?= url('genre/' . $g['slug']) ?>" class="chip"><?= e($g['name']) ?> <small><?= (int) $g['total'] ?></small></a>
            <?php endforeach; ?>
        </div>
    </section>
</aside>
