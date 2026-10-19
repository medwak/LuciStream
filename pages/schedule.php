<?php
$rows = db()->query('SELECT ' . series_card_sql() . " FROM series s WHERE s.status = 'Ongoing' AND s.release_day <> '' ORDER BY s.title COLLATE NOCASE")->fetchAll();
$byDay = array_fill_keys(WEEKDAYS, []);
foreach ($rows as $r) {
    if (isset($byDay[$r['release_day']])) {
        $byDay[$r['release_day']][] = $r;
    }
}
$today = date('l');
partial('header', ['section' => 'schedule', 'pageTitle' => 'Release Schedule']);
?>
<div class="container">
    <div class="page-head">
        <h1>Weekly Release Schedule</h1>
        <p class="muted">Times shown in <?= e(config('timezone')) ?>. Today is <?= e($today) ?>.</p>
    </div>
    <div class="tabs" data-tabs>
        <?php foreach (WEEKDAYS as $day): ?>
            <button class="tab<?= $day === $today ? ' active' : '' ?>" data-tab="<?= e($day) ?>">
                <?= e(substr($day, 0, 3)) ?> <small><?= count($byDay[$day]) ?></small>
            </button>
        <?php endforeach; ?>
    </div>
    <?php foreach ($byDay as $day => $list): ?>
        <section class="tab-panel" data-panel="<?= e($day) ?>" <?= $day === $today ? '' : 'hidden' ?>>
            <?php if ($list): ?>
                <div class="grid grid-wide">
                    <?php foreach ($list as $s): ?><?php partial('card', ['s' => $s]); ?><?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty">No releases scheduled for <?= e($day) ?>.</p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
<?php partial('footer'); ?>
