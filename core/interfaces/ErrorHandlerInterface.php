<?php

/**
 * Contrato para el manejo centralizado de errores y excepciones.
 * Permite sustituir la implementación (log a archivo, servicio externo, etc.)
 * sin afectar al resto del sistema (principio Abierto/Cerrado - SOLID).
 */
interface ErrorHandlerInterface
{
    public function handleException(Throwable $exception): void;

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool;

    public function logMessage(string $level, string $message): void;
}
