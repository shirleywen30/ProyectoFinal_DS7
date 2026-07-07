    <header class="admin-topbar">
        <div class="admin-brand">
            <span class="admin-brand-icon">&#128240;</span>
            <span><?= e(APP_NAME) ?></span>
        </div>
        <div class="admin-user">
            <span>Hola, <strong><?= e(currentUserName()) ?></strong> (<?= e($_SESSION['user_role'] ?? '') ?>)</span>
            <a href="<?= BASE_URL ?>/views/admin/logout.php" class="btn btn-small btn-danger">Cerrar sesión</a>
        </div>
    </header>

    <nav class="admin-menu">
        <a href="<?= BASE_URL ?>/views/admin/dashboard.php" class="<?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= BASE_URL ?>/views/admin/users/list.php" class="<?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">Usuarios</a>
        <a href="<?= BASE_URL ?>/views/admin/categories/list.php" class="<?= ($activeMenu ?? '') === 'categories' ? 'active' : '' ?>">Categorías</a>
        <a href="<?= BASE_URL ?>/views/admin/news/list.php" class="<?= ($activeMenu ?? '') === 'news' ? 'active' : '' ?>">Noticias</a>
        <a href="<?= BASE_URL ?>/views/admin/comments/list.php" class="<?= ($activeMenu ?? '') === 'comments' ? 'active' : '' ?>">Comentarios</a>
        <a href="<?= BASE_URL ?>/views/admin/news/reactions.php" class="<?= ($activeMenu ?? '') === 'reactions' ? 'active' : '' ?>">Reacciones</a>
        <a href="<?= BASE_URL ?>/index.php" class="menu-home">&#8962; Volver a HOME</a>
    </nav>

    <main class="admin-content">
        <?php flash(); ?>
