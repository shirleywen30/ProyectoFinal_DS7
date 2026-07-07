<?php

/**
 * Modelo de usuarios administrativos.
 * Gestiona credenciales, roles, estado (activo/inactivo) y política de
 * bloqueo por intentos fallidos (RNF-04).
 */
class UserModel extends BaseModel
{
    protected string $table = 'usuarios';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :identifier1 OR nombre = :identifier2 LIMIT 1');
        $stmt->execute(['identifier1' => $identifier, 'identifier2' => $identifier]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Búsqueda por nombre o email para el listado administrativo. */
    public function search(string $term, int $limit, int $offset): array
    {
        $sql = 'SELECT * FROM usuarios WHERE nombre LIKE :term1 OR email LIKE :term2 ORDER BY id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('term1', "%{$term}%");
        $stmt->bindValue('term2', "%{$term}%");
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countSearch(string $term): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM usuarios WHERE nombre LIKE :term1 OR email LIKE :term2');
        $stmt->execute(['term1' => "%{$term}%", 'term2' => "%{$term}%"]);
        return (int) $stmt->fetch()['total'];
    }

    public function incrementFailedAttempts(int $userId): void
    {
        $this->db->prepare('UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = :id')
            ->execute(['id' => $userId]);
    }

    public function lockAccount(int $userId, int $minutes): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :minutes MINUTE) WHERE id = :id');
        $stmt->execute(['minutes' => $minutes, 'id' => $userId]);
    }

    public function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function isLocked(array $user): bool
    {
        return !empty($user['bloqueado_hasta']) && strtotime($user['bloqueado_hasta']) > time();
    }

    public function toggleActive(int $userId, bool $active): bool
    {
        return $this->update($userId, ['activo' => $active ? 1 : 0]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM usuarios WHERE email = :email';
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }
}
