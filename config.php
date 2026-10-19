<?php
/**
 * LuciStream configuration.
 * Edit these values before deploying.
 */
return [
    'site_name'    => 'LuciStream',
    'tagline'      => 'Watch Donghua Online in HD',
    'description'  => 'Stream the latest Chinese animation (donghua) episodes in HD with English subtitles.',
    'db_path'      => __DIR__ . '/data/lucistream.sqlite',
    'upload_dir'   => __DIR__ . '/uploads',
    'per_page'     => 24,
    'timezone'     => 'UTC',

    // Set to false if your server cannot rewrite URLs (no .htaccess / mod_rewrite).
    // Links will then look like index.php?r=series/slug
    'pretty_urls'  => true,

    // URL folder the site lives in, e.g. '/lucistream'. null = detect automatically.
    'base_path'    => null,

    // Seed demo content on first run.
    'seed_demo'    => true,

    // Initial admin account, created on first run only. CHANGE THE PASSWORD after logging in.
    'admin_user'   => 'admin',
    'admin_pass'   => 'admin123',
];
