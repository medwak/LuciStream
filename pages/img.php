<?php
// Generates artwork placeholders (SVG) for series that have no uploaded poster/banner.
$kind = $arg === 'banner' ? 'banner' : 'poster';
$id = (int) preg_replace('/\D/', '', $parts[2] ?? '');
$st = db()->prepare('SELECT id, title, type FROM series WHERE id = ?');
$st->execute([$id]);
$s = $st->fetch();
if (!$s) {
    not_found();
}

$hash = crc32($s['title']);
$h1 = $hash % 360;
$h2 = ($h1 + 40 + ($hash >> 8) % 80) % 360;
[$w, $h] = $kind === 'banner' ? [1280, 540] : [300, 420];

// Wrap the title (posters only; banners sit behind the page's own heading) into lines that fit the artwork.
$maxChars = $kind === 'banner' ? 26 : 14;
$lines = [];
$line = '';
foreach (explode(' ', $s['title']) as $word) {
    if ($line !== '' && mb_strlen($line . ' ' . $word) > $maxChars) {
        $lines[] = $line;
        $line = $word;
    } else {
        $line = trim($line . ' ' . $word);
    }
}
$lines[] = $line;
$fontSize = $kind === 'banner' ? 64 : 30;
$lineH = $fontSize * 1.15;
$textY = $h * 0.5 - (count($lines) - 1) * $lineH / 2;
$textX = $kind === 'banner' ? $w * 0.68 : 20;
$anchor = $kind === 'banner' ? 'middle' : 'start';

$circles = '';
mt_srand($hash);
for ($i = 0; $i < 7; $i++) {
    $circles .= sprintf('<circle cx="%d" cy="%d" r="%d" fill="hsl(%d,90%%,70%%)" opacity="%.2f"/>',
        mt_rand(0, $w), mt_rand(0, $h), mt_rand((int) ($h / 12), (int) ($h / 2.5)), ($h1 + mt_rand(0, 60)) % 360, mt_rand(5, 18) / 100);
}

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
?>
<svg xmlns="http://www.w3.org/2000/svg" width="<?= $w ?>" height="<?= $h ?>" viewBox="0 0 <?= $w ?> <?= $h ?>">
    <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="hsl(<?= $h1 ?>,65%,32%)"/>
            <stop offset="1" stop-color="hsl(<?= $h2 ?>,70%,12%)"/>
        </linearGradient>
        <linearGradient id="fade" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0.4" stop-color="#000" stop-opacity="0"/>
            <stop offset="1" stop-color="#000" stop-opacity="0.75"/>
        </linearGradient>
    </defs>
    <rect width="100%" height="100%" fill="url(#g)"/>
    <?= $circles ?>
    <rect width="100%" height="100%" fill="url(#fade)"/>
<?php if ($kind === 'poster'): ?>
    <text x="<?= $textX ?>" y="<?= $textY ?>" fill="#fff" font-family="Segoe UI, Roboto, Arial, sans-serif" font-weight="800"
          font-size="<?= $fontSize ?>" text-anchor="<?= $anchor ?>">
        <?php foreach ($lines as $i => $l): ?><tspan x="<?= $textX ?>" dy="<?= $i ? $lineH : 0 ?>"><?= e($l) ?></tspan><?php endforeach; ?>
    </text>
    <text x="20" y="<?= $textY + count($lines) * $lineH ?>" fill="#fff" opacity="0.7"
          font-family="Segoe UI, Roboto, Arial, sans-serif" font-size="15" letter-spacing="3" text-anchor="<?= $anchor ?>"><?= e(strtoupper($s['type'])) ?> · DONGHUA</text>
<?php endif; ?>
</svg>
