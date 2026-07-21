<?php

/**
 * Modelo de comentarios de noticias.
 * Estados: pendiente, aprobado, bloqueado.
 */
class CommentModel extends BaseModel
{
    protected string $table = 'comentarios';

    public function byNews(int $newsId, ?string $onlyStatus = 'aprobado'): array
    {
        $sql = 'SELECT * FROM comentarios WHERE id_noticia = :nid';
        $params = ['nid' => $newsId];

        if ($onlyStatus !== null) {
            $sql .= ' AND estado = :estado';
            $params['estado'] = $onlyStatus;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Listado administrativo con filtros opcionales por estado y noticia. */
    public function filter(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $sql = "SELECT c.*, n.titulo AS noticia_titulo
                FROM comentarios c
                INNER JOIN noticias n ON n.id = c.id_noticia
                {$where}
                ORDER BY c.created_at DESC
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

    public function countFilter(array $filters): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM comentarios c INNER JOIN noticias n ON n.id = c.id_noticia {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $clauses[] = 'c.estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        if (!empty($filters['id_noticia'])) {
            $clauses[] = 'c.id_noticia = :id_noticia';
            $params['id_noticia'] = $filters['id_noticia'];
        }

        if (!empty($filters['desde'])) {
            $clauses[] = 'c.created_at >= :desde';
            $params['desde'] = $filters['desde'];
        }

        $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
        return [$where, $params];
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['estado' => $status]);
    }

    public function reply(int $id, string $respuesta, int $adminUserId): bool
    {
        return $this->update($id, [
            'respuesta'        => $respuesta,
            'id_usuario_admin' => $adminUserId,
        ]);
    }
}
