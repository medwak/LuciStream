<?php
declare(strict_types=1);

/**
 * Returns the shared PDO connection, creating the database on first use.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = $GLOBALS['config']['db_path'];
    $isNew = !file_exists($path);
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    if ($isNew) {
        db_install($pdo);
    }
    return $pdo;
}

function db_install(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        CREATE TABLE genres (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE
        );
        CREATE TABLE series (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            alt_title TEXT DEFAULT '',
            synopsis TEXT DEFAULT '',
            poster TEXT DEFAULT '',
            banner TEXT DEFAULT '',
            type TEXT NOT NULL DEFAULT 'ONA',
            status TEXT NOT NULL DEFAULT 'Ongoing',
            release_year INTEGER,
            studio TEXT DEFAULT '',
            rating REAL DEFAULT 0,
            release_day TEXT DEFAULT '',
            featured INTEGER NOT NULL DEFAULT 0,
            views INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );
        CREATE TABLE series_genres (
            series_id INTEGER NOT NULL REFERENCES series(id) ON DELETE CASCADE,
            genre_id INTEGER NOT NULL REFERENCES genres(id) ON DELETE CASCADE,
            PRIMARY KEY (series_id, genre_id)
        );
        CREATE TABLE episodes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            series_id INTEGER NOT NULL REFERENCES series(id) ON DELETE CASCADE,
            number REAL NOT NULL,
            title TEXT DEFAULT '',
            views INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            UNIQUE (series_id, number)
        );
        CREATE TABLE servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            episode_id INTEGER NOT NULL REFERENCES episodes(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            kind TEXT NOT NULL DEFAULT 'embed',
            url TEXT NOT NULL,
            sort INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX idx_episodes_created ON episodes(created_at);
        CREATE INDEX idx_series_updated ON series(updated_at);
    SQL);

    $cfg = $GLOBALS['config'];
    $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)')
        ->execute([$cfg['admin_user'], password_hash($cfg['admin_pass'], PASSWORD_DEFAULT), now()]);

    if (!empty($cfg['seed_demo'])) {
        require ROOT . '/includes/seed.php';
        seed_demo($pdo);
    }
}
