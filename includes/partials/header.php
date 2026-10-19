<?php
/** @var string|null $pageTitle */
/** @var string|null $metaDescription */
$siteName = config('site_name');
$fullTitle = !empty($pageTitle) ? $pageTitle . ' - ' . $siteName : $siteName . ' - ' . config('tagline');
$desc = $metaDescription ?? config('description');
$current = $section ?? '';
$navItems = ['' => 'Home', 'browse' => 'Browse', 'schedule' => 'Schedule', 'az' => 'A-Z List', 'mylist' => 'My List'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($desc) ?>">
    <?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
    <meta name="theme-color" content="#0f0f17">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body data-base="<?= e(BASE_PATH) ?>" data-api="<?= e(url('api/search')) ?>">
<header class="site-header">
    <div class="container header-inner">
        <button class="menu-toggle" aria-label="Open menu" data-toggle-menu>
            <span></span><span></span><span></span>
        </button>
        <a href="<?= url() ?>" class="logo">
            <span class="logo-mark">L</span><span class="logo-text"><?= e($siteName) ?></span>
        </a>
        <nav class="main-nav" id="main-nav">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="<?= url($route) ?>" class="<?= $current === $route ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <div class="nav-dropdown">
                <button type="button" class="<?= $current === 'genre' ? 'active' : '' ?>">Genres ▾</button>
                <div class="dropdown-menu">
                    <?php foreach (all_genres() as $g): ?>
                        <a href="<?= url('genre/' . $g['slug']) ?>"><?= e($g['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>
        <form class="search-form" action="<?= url('search') ?>" method="get" autocomplete="off">
            <?php if (!config('pretty_urls')): ?><input type="hidden" name="r" value="search"><?php endif; ?>
            <input type="search" name="q" placeholder="Search donghua…" value="<?= e($_GET['q'] ?? '') ?>" data-live-search aria-label="Search">
            <button type="submit" aria-label="Search">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M10 2a8 8 0 0 1 6.32 12.9l5.39 5.4-1.41 1.4-5.4-5.39A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12z"/></svg>
            </button>
            <div class="live-results" hidden></div>
        </form>
    </div>
</header>
<main class="site-main">
