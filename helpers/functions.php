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
    function asset(string $path): string
    {
        return BASE_URL . '/public/' . ltrim($path, '/');
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

if (!function_exists('buildPaginationLinks')) {
    /** Genera un arreglo de números de página útil para pintar la paginación. */
    function buildPaginationLinks(int $currentPage, int $totalPages): array
    {
        return range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2));
    }
}

if (!function_exists('clientIp')) {
    function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
