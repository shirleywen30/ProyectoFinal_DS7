<?php

/**
 * Punto de arranque único de la aplicación.
 * Toda vista/controlador debe incluir este archivo antes de ejecutar lógica.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Sesión segura: cookies HttpOnly + SameSite (mitiga XSS/CSRF - OWASP A05/A01)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');

require_once ROOT_PATH . '/core/interfaces/CrudInterface.php';
require_once ROOT_PATH . '/core/interfaces/ErrorHandlerInterface.php';
require_once ROOT_PATH . '/core/interfaces/HashServiceInterface.php';
require_once ROOT_PATH . '/core/ErrorHandler.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Validator.php';
require_once ROOT_PATH . '/core/PasswordHashService.php';
require_once ROOT_PATH . '/core/SignatureHashService.php';
require_once ROOT_PATH . '/core/Security.php';
require_once ROOT_PATH . '/core/BaseModel.php';
require_once ROOT_PATH . '/core/BaseController.php';
require_once ROOT_PATH . '/core/ImageUploader.php';
require_once ROOT_PATH . '/helpers/functions.php';

(new ErrorHandler())->register();

// Autoload perezoso de modelos y controladores por convención de nombre de archivo.
spl_autoload_register(function (string $class) {
    $paths = [
        ROOT_PATH . "/models/{$class}.php",
        ROOT_PATH . "/controllers/{$class}.php",
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Genera el token CSRF disponible para toda la sesión.
Security::generateCsrfToken();
