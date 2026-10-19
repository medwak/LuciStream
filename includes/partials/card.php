<?php
/**
 * Poster card. Expects $s (series row). Optional $ep (episode number) and $epTime for "latest episode" cards.
 */
$ep = $ep ?? null;
$href = $ep !== null ? episode_url($s['slug'], $ep) : series_url($s);
?>
<article class="card">
    <a href="<?= e($href) ?>" class="card-link" title="<?= e($s['title']) ?>">
        <div class="card-thumb">
            <img src="<?= e(poster_url($s)) ?>" alt="<?= e($s['title']) ?>" loading="lazy">
            <span class="badge badge-type"><?= e($s['type']) ?></span>
            <?php if ($ep !== null): ?>
                <span class="badge badge-ep">EP <?= e(format_ep($ep)) ?></span>
            <?php elseif (!empty($s['latest_ep'])): ?>
                <span class="badge badge-ep">EP <?= e(format_ep($s['latest_ep'])) ?></span>
            <?php endif; ?>
            <?php if ($s['status'] === 'Completed'): ?><span class="badge badge-status">Completed</span><?php endif; ?>
            <span class="card-play">▶</span>
        </div>
        <div class="card-body">
            <h3 class="card-title"><?= e($s['title']) ?></h3>
            <div class="card-meta">
                <?php if (!empty($epTime)): ?>
                    <span><?= e(time_ago($epTime)) ?></span>
                <?php else: ?>
                    <span><?= e($s['status']) ?></span>
                    <?php if ((float) $s['rating'] > 0): ?><span>★ <?= e(number_format((float) $s['rating'], 1)) ?></span><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </a>
</article>
