<?php

/**
 * Controlador base: agrupa comportamiento comun a todos los controladores
 * administrativos (autenticacion, CSRF, redirecciones, mensajes flash) -> DRY.
 */
abstract class BaseController
{
    /** Roles con acceso al panel administrativo (staff). El rol "usuario" es solo publico. */
    protected const STAFF_ROLES = ['admin', 'editor', 'supervisor'];

    /**
     * Exige sesion iniciada Y que el rol sea de staff (admin/editor/supervisor).
     * Una cuenta publica (rol "usuario") NUNCA debe pasar este chequeo, aunque
     * este logueada, porque no tiene acceso al panel administrativo.
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', self::STAFF_ROLES, true)) {
            $this->redirect(BASE_URL . '/views/admin/login.php');
        }
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireAuth();
        if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            http_response_code(403);
            die('Acceso denegado: no cuenta con los permisos necesarios.');
        }
    }

    /** Exige sesion iniciada (cualquier rol, incluido "usuario" publico). */
    protected function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect(BASE_URL . '/views/admin/login.php');
        }
    }

    protected function requireCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($token)) {
            http_response_code(419);
            die('Token de seguridad inválido o expirado. Regrese e intente nuevamente.');
        }
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
