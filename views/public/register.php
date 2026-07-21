<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (isLoggedIn()) {
    redirectTo(isStaff() ? BASE_URL . '/views/admin/dashboard.php' : BASE_URL . '/index.php');
}

$authController = new AuthController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $authController->register();
}

$pageTitle = 'Crear cuenta';
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
        <h1><?= e(APP_NAME) ?></h1>
        <p style="text-align:center;color:#6b7280;margin-top:-0.5rem">Cree su cuenta pública para reaccionar y comentar noticias</p>

        <?php flash(); ?>

        <form method="post" action="<?= BASE_URL ?>/views/public/register.php" novalidate>
            <?= Security::csrfField() ?>
            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" value="<?= old('nombre') ?>" required autofocus>
                <?php if (!empty($errors['nombre'])): ?><div class="field-error"><?= e($errors['nombre']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
                <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
                <small style="color:#6b7280">Entre <?= PASSWORD_MIN_LENGTH ?> y <?= PASSWORD_MAX_LENGTH ?> caracteres, combinando letras y números.</small>
                <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmar contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
                <?php if (!empty($errors['password_confirm'])): ?><div class="field-error"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn" style="width:100%">Crear cuenta</button>
        </form>
        <p style="text-align:center;margin-top:1rem">¿Ya tiene cuenta? <a href="<?= BASE_URL ?>/views/admin/login.php">Inicie sesión</a></p>
        <p style="text-align:center;margin-top:0.4rem"><a href="<?= BASE_URL ?>/index.php" style="text-decoration:none">Volver al sitio público</a></p>
    </div>
</div>
</body>
</html>
