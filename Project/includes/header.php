<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';

$title = $title ?? 'Crime Investigation';
$user = current_user();
$flash = flash();
$active = $active ?? '';
$nav = [
    'dashboard' => ['Dashboard', base_url('dashboard.php')],
    'cases' => ['Cases', base_url('modules/cases/index.php')],
    'officers' => ['Officers', base_url('modules/officers/index.php')],
    'suspects' => ['Suspects', base_url('modules/suspects/index.php')],
    'victims' => ['Victims', base_url('modules/victims/index.php')],
    'evidence' => ['Evidence', base_url('modules/evidence/index.php')],
    'teams' => ['Teams', base_url('modules/teams/index.php')],
    'locations' => ['Locations', base_url('modules/locations/index.php')],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> | CIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="brand-mark">CI</span>
            <div>
                <strong>CIMS</strong>
                <small>Investigation Desk</small>
            </div>
        </div>

        <nav>
            <?php foreach ($nav as $key => [$label, $href]): ?>
                <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= h($href) ?>"><?= h($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-user">
            <div class="user-mini">
                <span class="user-name"><?= h($user['name'] ?? 'Officer') ?></span>
                <small><?= h($user['email'] ?? '') ?></small>
            </div>
            <a href="<?= h(base_url('auth/logout.php')) ?>" class="logout-link">Logout</a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" type="button" aria-label="Toggle menu">☰</button>
            <div>
                <div class="eyebrow">Investigation Command</div>
                <h1><?= h($title) ?></h1>
            </div>
            <div class="topbar-meta"><?= h($user['rank'] ?? '') ?> · <?= h($user['department'] ?? '') ?></div>
        </header>
        <main class="content">
            <?php if ($flash): ?>
                <div class="toast <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>
            <div class="page-enter">
