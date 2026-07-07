<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$controller = new UserController();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$user = $id !== null ? $controller->find($id) : null;

if ($id !== null && $user === null) {
    redirectTo(BASE_URL . '/views/admin/users/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $controller->save($id);

    if (empty($errors)) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => $id === null ? 'Usuario creado correctamente.' : 'Usuario actualizado correctamente.'];
        redirectTo(BASE_URL . '/views/admin/users/list.php');
    }
}

$pageTitle = $id === null ? 'Nuevo usuario' : 'Editar usuario';
$activeMenu = 'users';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <h1><?= e($pageTitle) ?></h1>

    <form method="post" action="<?= BASE_URL ?>/views/admin/users/form.php<?= $id !== null ? '?id=' . $id : '' ?>">
        <?= Security::csrfField() ?>

        <div class="form-row">
            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($_POST['nombre'] ?? $user['nombre'] ?? '') ?>" required>
                <?php if (!empty($errors['nombre'])): ?><div class="field-error"><?= e($errors['nombre']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? $user['email'] ?? '') ?>" required>
                <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Contraseña <?= $id !== null ? '(dejar en blanco para no modificar)' : '' ?></label>
                <input type="password" id="password" name="password" minlength="<?= PASSWORD_MIN_LENGTH ?>" maxlength="<?= PASSWORD_MAX_LENGTH ?>" <?= $id === null ? 'required' : '' ?>>
                <small style="color:#6b7280">Entre <?= PASSWORD_MIN_LENGTH ?> y <?= PASSWORD_MAX_LENGTH ?> caracteres, combinando letras y números.</small>
                <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="rol">Rol</label>
                <?php $rolSel = $_POST['rol'] ?? $user['rol'] ?? 'editor'; ?>
                <select id="rol" name="rol">
                    <option value="admin" <?= $rolSel === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    <option value="editor" <?= $rolSel === 'editor' ? 'selected' : '' ?>>Editor</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="activo">Estado</label>
            <?php $activoVal = $_POST['activo'] ?? ($user['activo'] ?? 1); ?>
            <select id="activo" name="activo">
                <option value="1" <?= (string) $activoVal === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= (string) $activoVal === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <button type="submit" class="btn">Guardar</button>
        <a href="<?= BASE_URL ?>/views/admin/users/list.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
