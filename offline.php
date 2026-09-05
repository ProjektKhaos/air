<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$publicOrigin = rtrim((string) \ChiangMaiAirWatch\Config::get('app.public_origin', 'https://air.aberg.online'), '/');
$offlineUrl = $publicOrigin . url('offline.php');
$ogImageUrl = $publicOrigin . url('fb_og.png');
?>
<!doctype html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0c6db2">
    <meta name="description" content="<?= e(t('offline.body')) ?>">
    <link rel="canonical" href="<?= e($offlineUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?= e(locale() === 'th' ? 'th_TH' : 'en_US') ?>">
    <meta property="og:site_name" content="<?= e(t('app.name')) ?>">
    <meta property="og:title" content="<?= e(t('offline.title')) ?>">
    <meta property="og:description" content="<?= e(t('offline.body')) ?>">
    <meta property="og:url" content="<?= e($offlineUrl) ?>">
    <meta property="og:image" content="<?= e($ogImageUrl) ?>">
    <meta property="og:image:secure_url" content="<?= e($ogImageUrl) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1731">
    <meta property="og:image:height" content="909">
    <meta property="og:image:alt" content="Chiang Mai Air Watch — air-quality observations and model forecast">
    <script src="<?= e(asset_url('assets/js/theme-init.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
    <title><?= e(t('offline.title')) ?></title>
</head>
<body data-language="<?= e(locale()) ?>" data-base-url="<?= e(url()) ?>" data-default-station="air4thai:36t">
<script src="<?= e(asset_url('assets/js/sw-update.js')) ?>"></script>
<main class="app-shell">
    <div class="main-content">
        <section class="empty-state">
            <span class="brand-mark brand-mark-logo" aria-hidden="true"><img src="<?= e(asset_url('air_logo1.png')) ?>" width="221" height="221" alt=""></span>
            <h1><?= e(t('offline.title')) ?></h1>
            <p class="offline-marker"><?= e(t('common.offline')) ?></p>
            <p><?= e(t('offline.body')) ?></p>
            <a class="language-button" href="<?= e(url()) ?>">↻ <?= e(t('nav.home')) ?></a>
        </section>
        <section id="offline-snapshot" class="card" hidden>
            <p class="kicker"><?= e(t('home.station_status')) ?></p>
            <div class="aqi-reading compact"><span id="offline-aqi">—</span><small id="offline-reading-label">TH AQI</small></div>
            <div class="metric-grid two"><div><span>PM2.5</span><strong id="offline-pm25">—</strong></div><div><span><?=e(t('station.measured'))?></span><strong id="offline-time">—</strong></div></div>
            <p id="offline-stored" class="muted"></p>
        </section>
    </div>
</main>
<script src="<?= e(asset_url('assets/js/app.js')) ?>" defer></script>
<script src="<?= e(asset_url('assets/js/offline.js')) ?>" defer></script>
</body>
</html>
