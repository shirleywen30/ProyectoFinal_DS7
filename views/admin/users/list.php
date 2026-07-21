<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$controller = new UserController();

$term = Validator::sanitizeString($_GET['buscar'] ?? '');
$page = (int) ($_GET['page'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $controller->toggleActive((int) $_POST['id']);
    redirectTo(BASE_URL . '/views/admin/users/list.php?' . http_build_query(['buscar' => $term, 'page' => $page]));
}

$result = $controller->listPaginated($term, $page);

$pageTitle = 'Usuarios';
$activeMenu = 'users';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.8rem;align-items:center">
        <h1 style="margin:0">Gestión de usuarios</h1>
        <a href="<?= BASE_URL ?>/views/admin/users/form.php" class="btn">+ Nuevo usuario</a>
    </div>

    <form method="get" action="<?= BASE_URL ?>/views/admin/users/list.php" class="filters-bar">
        <div class="field">
            <label>Buscar</label>
            <input type="search" name="buscar" placeholder="Nombre o correo..." value="<?= e($term) ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Buscar</button>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($result['items'])): ?>
                    <tr><td colspan="5">No se encontraron usuarios.</td></tr>
                <?php endif; ?>
                <?php foreach ($result['items'] as $user): ?>
                    <tr>
                        <td data-label="Nombre"><?= e($user['nombre']) ?></td>
                        <td data-label="Correo"><?= e($user['email']) ?></td>
                        <td data-label="Rol"><span class="badge badge-muted"><?= e($user['rol']) ?></span></td>
                        <td data-label="Estado">
                            <?php if ((int) $user['activo'] === 1): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <a class="btn btn-small" href="<?= BASE_URL ?>/views/admin/users/form.php?id=<?= (int) $user['id'] ?>">Editar</a>
                                <form method="post" action="<?= BASE_URL ?>/views/admin/users/list.php" data-confirm="¿Cambiar el estado de este usuario?">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                    <button type="submit" class="btn btn-small <?= $user['activo'] ? 'btn-danger' : 'btn-success' ?>">
                                        <?= $user['activo'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php foreach (buildPaginationLinks($result['page'], $result['totalPages']) as $p): ?>
            <?php if ($p === $result['page']): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="?buscar=<?= urlencode($term) ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
