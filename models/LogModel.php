<?php

/**
 * Registra y consulta los intentos de inicio de sesión (IP, fecha, éxito/fallo).
 * Requisito: "Registrar todos los intentos de login (IP y fecha)".
 */
class LogModel extends BaseModel
{
    protected string $table = 'login_logs';

    public function record(string $usuario, string $ip, bool $exito, string $userAgent = ''): int
    {
        return $this->create([
            'usuario'    => $usuario,
            'ip_address' => $ip,
            'exito'      => $exito ? 1 : 0,
            'user_agent' => $userAgent,
            'fecha_hora' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM login_logs ORDER BY fecha_hora DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
