<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$controller = new CategoryController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $controller->delete((int) $_POST['id']);
    redirectTo(BASE_URL . '/views/admin/categories/list.php');
}

$categories = $controller->listAll();

$pageTitle = 'Categorías';
$activeMenu = 'categories';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.8rem;align-items:center">
        <h1 style="margin:0">Categorías</h1>
        <a href="<?= BASE_URL ?>/views/admin/categories/form.php" class="btn">+ Nueva categoría</a>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4">No hay categorías registradas.</td></tr>
                <?php endif; ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td data-label="Nombre"><?= e($cat['nombre']) ?></td>
                        <td data-label="Descripción"><?= e($cat['descripcion']) ?></td>
                        <td data-label="Estado">
                            <?php if ((int) $cat['activo'] === 1): ?>
                                <span class="badge badge-success">Activa</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <a class="btn btn-small" href="<?= BASE_URL ?>/views/admin/categories/form.php?id=<?= (int) $cat['id'] ?>">Editar</a>
                                <form method="post" action="<?= BASE_URL ?>/views/admin/categories/list.php" data-confirm="¿Eliminar esta categoría? Esta acción no se puede deshacer.">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
