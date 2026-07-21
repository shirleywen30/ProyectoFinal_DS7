<?php

/**
 * Reacciones ("me gusta", etc.) de usuarios públicos sobre noticias.
 * Toda reacción nueva queda en estado "pendiente" hasta que un administrador
 * o supervisor la aprueba; solo las reacciones "aprobado" cuentan en los
 * totales que se muestran públicamente.
 */
class ReactionModel extends BaseModel
{
    protected string $table = 'reacciones';

    /**
     * Registra una solicitud de reacción (queda pendiente de aprobación),
     * evitando duplicados por IP + noticia + tipo.
     */
    public function addReaction(int $newsId, string $ip, string $tipo, ?int $userId = null): bool
    {
        if ($this->alreadyReacted($newsId, $ip, $tipo)) {
            return false;
        }

        $this->create([
            'id_noticia' => $newsId,
            'id_usuario' => $userId,
            'tipo'       => $tipo,
            'ip_address' => $ip,
            'estado'     => 'pendiente',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /** true si esta IP ya tiene una solicitud (pendiente o aprobada) de este tipo para esta noticia. */
    public function alreadyReacted(int $newsId, string $ip, ?string $tipo = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM reacciones WHERE id_noticia = :nid AND ip_address = :ip AND estado != :rechazado';
        $params = ['nid' => $newsId, 'ip' => $ip, 'rechazado' => 'rechazado'];

        if ($tipo !== null) {
            $sql .= ' AND tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /** Devuelve el estado ('pendiente'/'aprobado'/'rechazado'/null) de la reacción de esta IP para un tipo dado. */
    public function myStatus(int $newsId, string $ip, string $tipo): ?string
    {
        $stmt = $this->db->prepare('SELECT estado FROM reacciones WHERE id_noticia = :nid AND ip_address = :ip AND tipo = :tipo LIMIT 1');
        $stmt->execute(['nid' => $newsId, 'ip' => $ip, 'tipo' => $tipo]);
        $row = $stmt->fetch();
        return $row === false ? null : $row['estado'];
    }

    /** Total de reacciones APROBADAS de una noticia (lo que se muestra públicamente). */
    public function countByNews(int $newsId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM reacciones WHERE id_noticia = :nid AND estado = 'aprobado'");
        $stmt->execute(['nid' => $newsId]);
        return (int) $stmt->fetch()['total'];
    }

    /** Conteo de reacciones APROBADAS de una noticia, agrupado por tipo (ej. ['like' => 3, 'eco' => 1]). */
    public function countByNewsGroupedByType(int $newsId): array
    {
        $stmt = $this->db->prepare("SELECT tipo, COUNT(*) AS total FROM reacciones WHERE id_noticia = :nid AND estado = 'aprobado' GROUP BY tipo");
        $stmt->execute(['nid' => $newsId]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['tipo']] = (int) $row['total'];
        }
        return $counts;
    }

    /** Estadísticas de reacciones aprobadas agrupadas por noticia, para el panel admin. */
    public function statsByNews(): array
    {
        $sql = "SELECT n.id, n.titulo, COUNT(r.id) AS total_reacciones
                FROM noticias n
                LEFT JOIN reacciones r ON r.id_noticia = n.id AND r.estado = 'aprobado'
                GROUP BY n.id, n.titulo
                ORDER BY total_reacciones DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Solicitudes de reacción pendientes de aprobación, para el panel admin. */
    public function pending(): array
    {
        $sql = "SELECT r.*, n.titulo AS noticia_titulo
                FROM reacciones r
                INNER JOIN noticias n ON n.id = r.id_noticia
                WHERE r.estado = 'pendiente'
                ORDER BY r.created_at ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Listado completo (cualquier estado) con filtros simples, para el panel admin. */
    public function filter(array $filters, int $limit, int $offset): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $clauses[] = 'r.estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

        $sql = "SELECT r.*, n.titulo AS noticia_titulo
                FROM reacciones r
                INNER JOIN noticias n ON n.id = r.id_noticia
                {$where}
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['estado' => $status]);
    }
}
