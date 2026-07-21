<?php

/**
 * Clase responsable de sanitizar y validar datos de entrada (SRP - SOLID).
 * Centraliza reglas de validación para evitar duplicación (DRY) y mitigar
 * XSS / inyección (OWASP A03: Injection, A07: Identification Failures).
 */
class Validator
{
    private array $errors = [];

    /** Elimina espacios extremos y etiquetas peligrosas de una cadena. */
    public static function sanitizeString(?string $value): string
    {
        $value = trim($value ?? '');
        $value = strip_tags($value);
        return $value;
    }

    /** Sanitiza texto que permite párrafos (contenido de noticias) sin HTML peligroso. */
    public static function sanitizeRichText(?string $value): string
    {
        $value = trim($value ?? '');
        // Solo se permiten etiquetas básicas de párrafo/formato, el resto se elimina.
        return strip_tags($value, '<p><br><strong><em><ul><ol><li><b><i>');
    }

    public static function sanitizeEmail(?string $value): string
    {
        $value = trim($value ?? '');
        return filter_var($value, FILTER_SANITIZE_EMAIL) ?: '';
    }

    public static function sanitizeInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public function isRequired($value, string $field, string $label): self
    {
        if ($value === null || trim((string) $value) === '') {
            $this->errors[$field] = "El campo {$label} es obligatorio.";
        }
        return $this;
    }

    public function isEmail($value, string $field, string $label): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El campo {$label} debe ser un correo electrónico válido.";
        }
        return $this;
    }

    public function isLength($value, string $field, string $label, int $min, int $max): self
    {
        $len = mb_strlen((string) $value);
        if (!empty($value) && ($len < $min || $len > $max)) {
            $this->errors[$field] = "El campo {$label} debe tener entre {$min} y {$max} caracteres.";
        }
        return $this;
    }

    /**
     * Valida la política de contraseñas: 8 a 12 caracteres, con al menos
     * una letra y un número (RNF-05: robustez de credenciales).
     */
    public function isValidPassword($value, string $field = 'password', string $label = 'contraseña'): self
    {
        $value = (string) $value;
        $len = strlen($value);

        if ($len < PASSWORD_MIN_LENGTH || $len > PASSWORD_MAX_LENGTH) {
            $this->errors[$field] = "La {$label} debe tener entre " . PASSWORD_MIN_LENGTH . " y " . PASSWORD_MAX_LENGTH . " caracteres.";
            return $this;
        }

        if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value)) {
            $this->errors[$field] = "La {$label} debe combinar letras y números.";
        }

        return $this;
    }

    public function isInArray($value, array $allowed, string $field, string $label): self
    {
        if (!empty($value) && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "El valor de {$label} no es válido.";
        }
        return $this;
    }

    public function isNumeric($value, string $field, string $label): self
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = "El campo {$label} debe ser numérico.";
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function reset(): self
    {
        $this->errors = [];
        return $this;
    }
}
