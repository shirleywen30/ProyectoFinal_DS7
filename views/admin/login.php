<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (isLoggedIn()) {
    redirectTo(BASE_URL . '/views/admin/dashboard.php');
}

$authController = new AuthController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $authController->login();
}

$pageTitle = 'Iniciar sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <h1>&#128240; <?= e(APP_NAME) ?></h1>
        <p style="text-align:center;color:#6b7280;margin-top:-0.5rem">Acceso administrativo</p>

        <?php flash(); ?>
        <?php if (!empty($errors['login'])): ?>
            <div class="alert alert-error"><?= e($errors['login']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/views/admin/login.php" novalidate>
            <?= Security::csrfField() ?>
            <div class="form-group">
                <label for="usuario">Usuario o correo electrónico</label>
                <input type="text" id="usuario" name="usuario" value="<?= old('usuario') ?>" required autofocus>
                <?php if (!empty($errors['usuario'])): ?><div class="field-error"><?= e($errors['usuario']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
                <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn" style="width:100%">Ingresar</button>
        </form>
        <p style="text-align:center;margin-top:1rem"><a href="<?= BASE_URL ?>/index.php">&larr; Volver al sitio público</a></p>
    </div>
</div>
</body>
</html>
