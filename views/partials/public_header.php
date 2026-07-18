<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal de noticias: deportes, tecnología, cultura, política y eventos.">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= asset('css/public.css') ?>">
</head>
<body>
<div class="site-wrapper">
    <header class="site-header">
        <div class="site-header-inner">
            <a href="<?= BASE_URL ?>/index.php" class="site-brand">&#128240; <?= e(APP_NAME) ?></a>
            <nav class="site-menu">
                <a href="<?= BASE_URL ?>/index.php" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Inicio</a>
                <a href="<?= BASE_URL ?>/views/public/all-news.php" class="<?= ($activeMenu ?? '') === 'news' ? 'active' : '' ?>">Noticias</a>
                <a href="<?= BASE_URL ?>/views/public/about.php" class="<?= ($activeMenu ?? '') === 'about' ? 'active' : '' ?>">Nosotros</a>
            </nav>
            <div class="site-account">
                <?php if (isLoggedIn()): ?>
                    <span class="site-account-name">Hola, <strong><?= e(currentUserName()) ?></strong></span>
                    <a href="<?= BASE_URL ?>/views/admin/dashboard.php" class="btn btn-small">Panel admin</a>
                    <a href="<?= BASE_URL ?>/views/admin/logout.php" class="btn btn-small btn-secondary">Cerrar sesión</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/views/admin/login.php" class="btn btn-small">&#128100; Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="site-content">
        <?php flash(); ?>
