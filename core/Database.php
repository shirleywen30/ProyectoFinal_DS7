<?php

/**
 * Clase responsable únicamente de administrar la conexión PDO (SRP - SOLID).
 * Implementa Singleton para reutilizar una única conexión por petición
 * y evitar múltiples handshakes con MySQL.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $config = require ROOT_PATH . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // fuerza consultas preparadas reales (mitiga inyección SQL)
            ]);
        } catch (PDOException $e) {
            // No se expone el detalle de la excepción al usuario final (OWASP - manejo de errores)
            throw new RuntimeException('No fue posible conectar a la base de datos.');
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Atajo para preparar y ejecutar sentencias con parámetros vinculados.
     * Todas las consultas del sistema deben pasar por aquí para garantizar
     * el uso de sentencias preparadas (prevención de SQL Injection - OWASP A03).
     */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    private function __clone()
    {
    }
}
