<?php

/**
 * Clase responsable de las operaciones transversales de seguridad:
 * tokens CSRF, hashing de contraseñas y firma digital de integridad.
 * (SRP - SOLID; RNF-01 OWASP, RNF-06 Integridad).
 */
class Security
{
    private const CSRF_SESSION_KEY = 'csrf_token';

    /** Genera (o reutiliza) el token CSRF de la sesión actual. */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION[self::CSRF_SESSION_KEY])) {
            $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF_SESSION_KEY];
    }

    /**
     * Valida el token CSRF recibido en peticiones POST/PUT/DELETE.
     * Mitiga OWASP A01/CSRF (Cross-Site Request Forgery).
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::CSRF_SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::CSRF_SESSION_KEY], $token);
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">';
    }

    /** Hash seguro de contraseñas usando bcrypt (OWASP: almacenamiento de credenciales). */
    public static function hashPassword(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    /**
     * Genera una firma digital (HMAC-SHA256) a partir de los campos clave
     * de una noticia, para garantizar su integridad (RNF-06).
     */
    public static function generateSignature(array $fields): string
    {
        $payload = implode('|', $fields);
        return hash_hmac('sha256', $payload, SIGNATURE_SECRET_KEY);
    }

    public static function verifySignature(array $fields, string $signature): bool
    {
        $expected = self::generateSignature($fields);
        return hash_equals($expected, $signature);
    }

    /** Genera un nombre de archivo aleatorio y seguro conservando la extensión. */
    public static function randomFileName(string $originalExtension): string
    {
        return bin2hex(random_bytes(16)) . '.' . $originalExtension;
    }
}
