<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$controller = new CategoryController();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$category = $id !== null ? $controller->find($id) : null;

if ($id !== null && $category === null) {
    redirectTo(BASE_URL . '/views/admin/categories/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $controller->save($id);

    if (empty($errors)) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => $id === null ? 'Categoría creada correctamente.' : 'Categoría actualizada correctamente.'];
        redirectTo(BASE_URL . '/views/admin/categories/list.php');
    }
}

$pageTitle = $id === null ? 'Nueva categoría' : 'Editar categoría';
$activeMenu = 'categories';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <h1><?= e($pageTitle) ?></h1>

    <form method="post" action="<?= BASE_URL ?>/views/admin/categories/form.php<?= $id !== null ? '?id=' . $id : '' ?>">
        <?= Security::csrfField() ?>

        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($_POST['nombre'] ?? $category['nombre'] ?? '') ?>" required>
            <?php if (!empty($errors['nombre'])): ?><div class="field-error"><?= e($errors['nombre']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" style="min-height:80px"><?= e($_POST['descripcion'] ?? $category['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="activo">Estado</label>
            <?php $activoVal = $_POST['activo'] ?? ($category['activo'] ?? 1); ?>
            <select id="activo" name="activo">
                <option value="1" <?= (string) $activoVal === '1' ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= (string) $activoVal === '0' ? 'selected' : '' ?>>Inactiva</option>
            </select>
        </div>

        <button type="submit" class="btn">Guardar</button>
        <a href="<?= BASE_URL ?>/views/admin/categories/list.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
