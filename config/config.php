<?php
/**
 * Constantes globales de la aplicación.
 * Calcula BASE_URL de forma dinámica para que el proyecto funcione
 * sin importar el nombre de la carpeta dentro de la raíz de WAMP.
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));
}

if (!defined('BASE_URL')) {
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/')) : '';
    $base = $documentRoot !== '' ? str_replace($documentRoot, '', ROOT_PATH) : '';
    define('BASE_URL', $base === '' ? '' : $base);
}

// Rutas de sistema de archivos
define('UPLOAD_NEWS_PATH', ROOT_PATH . '/public/uploads/news/');
define('UPLOAD_THUMB_PATH', ROOT_PATH . '/public/uploads/thumbnails/');
define('LOGS_PATH', ROOT_PATH . '/logs/');

// Rutas públicas (URL) de archivos subidos
define('UPLOAD_NEWS_URL', BASE_URL . '/public/uploads/news/');
define('UPLOAD_THUMB_URL', BASE_URL . '/public/uploads/thumbnails/');

// Clave secreta usada para HMAC de firma digital de integridad (RNF-06)
// En un entorno productivo debe definirse como variable de entorno.
define('SIGNATURE_SECRET_KEY', 'c9f1b3e2a7d94c6f8e0b1a2d3c4f5e6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2');

// Política de seguridad de cuentas (RNF-04 / RNF-05)
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_MINUTES', 15);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 12);

// Paginación
define('NEWS_PER_PAGE_ADMIN', 5);
define('NEWS_PER_PAGE_PUBLIC', 6);
define('EXCERPT_LENGTH', 100);

// Imágenes
define('MIN_NEWS_IMAGES', 3);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);
define('MAX_IMAGE_SIZE_BYTES', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_MIMES', ['image/jpeg', 'image/png', 'image/webp']);

define('APP_NAME', 'Sistema de Noticias');
define('APP_ENV', 'development'); // development | production
