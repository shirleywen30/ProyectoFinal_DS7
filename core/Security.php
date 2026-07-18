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

    /**
     * Hash seguro de contraseñas (OWASP: almacenamiento de credenciales) y
     * firma digital de integridad (RNF-06) delegadas en implementaciones
     * concretas de HashServiceInterface (contrato unificado de servicios
     * criptográficos - SOLID: Inversión de Dependencias).
     */
    public static function hashPassword(string $plainPassword): string
    {
        return (new PasswordHashService())->hash($plainPassword);
    }

    public static function verifyPassword(string $plainPassword, string $hash): bool
    {
        return (new PasswordHashService())->verify($plainPassword, $hash);
    }

    /**
     * Genera una firma digital (HMAC-SHA256) a partir de los campos clave
     * de una noticia, para garantizar su integridad (RNF-06).
     */
    public static function generateSignature(array $fields): string
    {
        return (new SignatureHashService())->hash(implode('|', $fields));
    }

    public static function verifySignature(array $fields, string $signature): bool
    {
        return (new SignatureHashService())->verify(implode('|', $fields), $signature);
    }

    /** Genera un nombre de archivo aleatorio y seguro conservando la extensión. */
    public static function randomFileName(string $originalExtension): string
    {
        return bin2hex(random_bytes(16)) . '.' . $originalExtension;
    }
}
