    <header class="admin-topbar">
        <div class="admin-brand">
            <span><?= e(APP_NAME) ?></span>
        </div>
        <div class="admin-user">
            <span>Hola, <strong><?= e(currentUserName()) ?></strong> (<?= e($_SESSION['user_role'] ?? '') ?>)</span>
            <a href="<?= BASE_URL ?>/views/admin/logout.php" class="btn btn-small btn-danger">Cerrar sesión</a>
        </div>
    </header>

    <?php
    $__role = $_SESSION['user_role'] ?? '';
    $__isAdmin = $__role === 'admin';
    $__isPrivileged = in_array($__role, ['admin', 'supervisor'], true);
    ?>
    <nav class="admin-menu">
        <a href="<?= BASE_URL ?>/views/admin/dashboard.php" class="<?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <?php if ($__isAdmin): ?>
            <a href="<?= BASE_URL ?>/views/admin/users/list.php" class="<?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">Usuarios</a>
        <?php endif; ?>
        <?php if ($__isPrivileged): ?>
            <a href="<?= BASE_URL ?>/views/admin/categories/list.php" class="<?= ($activeMenu ?? '') === 'categories' ? 'active' : '' ?>">Categorías</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/views/admin/news/list.php" class="<?= ($activeMenu ?? '') === 'news' ? 'active' : '' ?>">Noticias</a>
        <?php if ($__isPrivileged): ?>
            <a href="<?= BASE_URL ?>/views/admin/comments/list.php" class="<?= ($activeMenu ?? '') === 'comments' ? 'active' : '' ?>">Comentarios</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/views/admin/news/reactions.php" class="<?= ($activeMenu ?? '') === 'reactions' ? 'active' : '' ?>">Reacciones</a>
        <a href="<?= BASE_URL ?>/index.php" class="menu-home">Volver a HOME</a>
    </nav>

    <main class="admin-content">
        <?php flash(); ?>
