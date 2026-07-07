<?php

/**
 * Manejador centralizado de errores y excepciones no controladas.
 * Evita filtrar información sensible (rutas, queries, stack traces) a
 * los usuarios finales -> OWASP A05: Security Misconfiguration.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $this->logMessage('ERROR', "$message en $file:$line");

        if (APP_ENV !== 'production') {
            echo "<div style='background:#fee;color:#900;padding:10px;font-family:monospace'>"
                . htmlspecialchars("$message ($file:$line)") . "</div>";
        }

        return true;
    }

    public function handleException(Throwable $exception): void
    {
        $this->logMessage('EXCEPTION', $exception->getMessage() . ' en ' . $exception->getFile() . ':' . $exception->getLine());

        http_response_code(500);

        if (APP_ENV !== 'production') {
            echo '<pre style="background:#fee;color:#900;padding:15px">' . htmlspecialchars($exception->getMessage() . "\n" . $exception->getTraceAsString()) . '</pre>';
        } else {
            echo '<h1>Ha ocurrido un error inesperado</h1><p>Por favor intente nuevamente más tarde.</p>';
        }
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $this->logMessage('FATAL', $error['message'] . ' en ' . $error['file'] . ':' . $error['line']);
        }
    }

    public function logMessage(string $level, string $message): void
    {
        $line = sprintf('[%s] [%s] %s%s', date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
        @file_put_contents(LOGS_PATH . 'error.log', $line, FILE_APPEND | LOCK_EX);
    }
}
