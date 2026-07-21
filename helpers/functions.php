<?php

/**
 * Funciones auxiliares de uso general en las vistas.
 * Se mantienen como funciones puras y reutilizables (DRY).
 */

if (!function_exists('e')) {
    /** Escapa una cadena para salida segura en HTML (mitiga XSS - OWASP A03). */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('truncate')) {
    function truncate(string $text, int $length = 100): string
    {
        $plain = trim(strip_tags($text));
        if (mb_strlen($plain) <= $length) {
            return $plain;
        }
        return mb_substr($plain, 0, $length) . '...';
    }
}

if (!function_exists('formatDate')) {
    function formatDate(?string $date, string $format = 'd/m/Y H:i'): string
    {
        if (empty($date)) {
            return '';
        }
        return (new DateTime($date))->format($format);
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('flash')) {
    /** Imprime (y limpia) el mensaje flash almacenado en sesión. */
    function flash(): void
    {
        if (empty($_SESSION['flash'])) {
            return;
        }
        $type = e($_SESSION['flash']['type']);
        $message = e($_SESSION['flash']['message']);
        echo "<div class=\"alert alert-{$type}\">{$message}</div>";
        unset($_SESSION['flash']);
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        return e($_POST[$key] ?? $default);
    }
}

if (!function_exists('asset')) {
    /** Genera la URL de un archivo estático con "cache busting" (?v=fecha de modificación),
     * para que el navegador siempre cargue la versión actual de CSS/JS tras cada cambio. */
    function asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $fullPath = ROOT_PATH . '/public/' . $relative;
        $version = is_file($fullPath) ? filemtime($fullPath) : time();

        return BASE_URL . '/public/' . $relative . '?v=' . $version;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('currentUserName')) {
    function currentUserName(): string
    {
        return $_SESSION['user_name'] ?? '';
    }
}

if (!function_exists('isStaff')) {
    /** true si hay sesión iniciada con rol administrativo (admin/editor/supervisor). */
    function isStaff(): bool
    {
        return isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin', 'editor', 'supervisor'], true);
    }
}

if (!function_exists('isPublicUser')) {
    /** true si hay sesión iniciada con la cuenta pública normal (rol "usuario"). */
    function isPublicUser(): bool
    {
        return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'usuario';
    }
}

if (!function_exists('buildPaginationLinks')) {
    /** Genera un arreglo de números de página útil para pintar la paginación. */
    function buildPaginationLinks(int $currentPage, int $totalPages): array
    {
        return range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2));
    }
}

if (!function_exists('embedVideoUrl')) {
    /** Convierte un enlace de YouTube/Vimeo en su URL de embed; null si no se reconoce. */
    function embedVideoUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }
}

if (!function_exists('clientIp')) {
    function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
