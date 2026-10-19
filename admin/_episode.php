<?php
declare(strict_types=1);

/**
 * Validates posted server rows. Returns [servers, errors].
 */
function posted_servers(): array
{
    $servers = [];
    $errors = [];
    $names = (array) ($_POST['server_name'] ?? []);
    $kinds = (array) ($_POST['server_kind'] ?? []);
    $urls = (array) ($_POST['server_url'] ?? []);
    foreach ($urls as $i => $u) {
        $u = trim((string) $u);
        if ($u === '') {
            continue;
        }
        // Accept a pasted <iframe> embed code and pull out its src.
        if (stripos($u, '<iframe') !== false && preg_match('/src=["\']([^"\']+)["\']/i', $u, $m)) {
            $u = html_entity_decode($m[1]);
        }
        if (str_starts_with($u, '//')) {
            $u = 'https:' . $u;
        }
        if (!is_safe_url($u)) {
            $errors[] = 'Server URL #' . ($i + 1) . ' is not a valid http(s) URL.';
            continue;
        }
        $kind = ($kinds[$i] ?? '') === 'video' ? 'video' : 'embed';
        $name = trim((string) ($names[$i] ?? '')) ?: 'Server ' . (count($servers) + 1);
        $servers[] = ['name' => mb_substr($name, 0, 40), 'kind' => $kind, 'url' => $u];
    }
    return [$servers, $errors];
}

function save_servers(int $episodeId, array $servers): void
{
    db()->prepare('DELETE FROM servers WHERE episode_id = ?')->execute([$episodeId]);
    $ins = db()->prepare('INSERT INTO servers (episode_id, name, kind, url, sort) VALUES (?, ?, ?, ?, ?)');
    foreach ($servers as $i => $srv) {
        $ins->execute([$episodeId, $srv['name'], $srv['kind'], $srv['url'], $i]);
    }
}

function touch_series(int $seriesId): void
{
    db()->prepare('UPDATE series SET updated_at = ? WHERE id = ?')->execute([now(), $seriesId]);
}

function server_rows(array $servers, int $min = 2): void
{
    while (count($servers) < $min) {
        $servers[] = ['name' => '', 'kind' => 'embed', 'url' => ''];
    }
    ?>
    <div class="servers-edit" data-servers>
        <?php foreach ($servers as $i => $srv): ?>
            <div class="server-row">
                <input name="server_name[]" value="<?= e($srv['name']) ?>" placeholder="Server <?= $i + 1 ?>" aria-label="Server name">
                <select name="server_kind[]" aria-label="Source type">
                    <option value="embed" <?= $srv['kind'] === 'embed' ? 'selected' : '' ?>>Embed (iframe)</option>
                    <option value="video" <?= $srv['kind'] === 'video' ? 'selected' : '' ?>>Direct video (mp4/webm)</option>
                </select>
                <input name="server_url[]" value="<?= e($srv['url']) ?>" placeholder="https://… or paste &lt;iframe&gt; code" aria-label="Source URL">
                <button type="button" class="btn btn-small btn-ghost" data-remove-row aria-label="Remove">✕</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-small btn-secondary" data-add-row>+ Add server</button>
    <?php
}
