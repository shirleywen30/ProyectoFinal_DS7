<?php

/**
 * Modelo de noticias. Concentra las consultas de listado, filtros,
 * búsqueda, paginación y detalle, reutilizando JOINs comunes (DRY).
 */
class NewsModel extends BaseModel
{
    protected string $table = 'noticias';

    private const BASE_SELECT = "SELECT n.*, c.nombre AS categoria_nombre, u.nombre AS creador_nombre
                                  FROM noticias n
                                  INNER JOIN categorias c ON c.id = n.id_categoria
                                  INNER JOIN usuarios u ON u.id = n.id_usuario";

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE n.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Listado administrativo con filtros de categoría, fecha, estado y texto. */
    public function filterAdmin(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $sql = self::BASE_SELECT . " {$where} ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countFilterAdmin(array $filters): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM noticias n
                INNER JOIN categorias c ON c.id = n.id_categoria
                INNER JOIN usuarios u ON u.id = n.id_usuario {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['id_categoria'])) {
            $clauses[] = 'n.id_categoria = :id_categoria';
            $params['id_categoria'] = $filters['id_categoria'];
        }

        if (!empty($filters['fecha'])) {
            $clauses[] = 'DATE(n.created_at) = :fecha';
            $params['fecha'] = $filters['fecha'];
        }

        if (isset($filters['publicado']) && $filters['publicado'] !== '') {
            $clauses[] = 'n.publicado = :publicado';
            $params['publicado'] = $filters['publicado'];
        }

        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $clauses[] = 'n.activo = :activo';
            $params['activo'] = $filters['activo'];
        }

        if (!empty($filters['buscar'])) {
            $clauses[] = '(LOWER(n.titulo) LIKE LOWER(:buscar1) OR LOWER(n.autor) LIKE LOWER(:buscar2) OR LOWER(u.nombre) LIKE LOWER(:buscar3))';
            $like = '%' . $filters['buscar'] . '%';
            $params['buscar1'] = $like;
            $params['buscar2'] = $like;
            $params['buscar3'] = $like;
        }

        $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
        return [$where, $params];
    }

    /** Noticias publicadas y activas para el sitio público, con filtros. */
    public function publicList(array $filters, int $limit, int $offset): array
    {
        $filters['publicado'] = 1;
        $filters['activo'] = 1;
        return $this->filterAdmin($filters, $limit, $offset);
    }

    public function countPublicList(array $filters): int
    {
        $filters['publicado'] = 1;
        $filters['activo'] = 1;
        return $this->countFilterAdmin($filters);
    }

    /** Las 3 noticias más recientes publicadas, para la portada. */
    public function latestPublished(int $count = 3): array
    {
        $sql = self::BASE_SELECT . ' WHERE n.publicado = 1 AND n.activo = 1 ORDER BY n.created_at DESC LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $count, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function publicDetail(int $id): ?array
    {
        $sql = self::BASE_SELECT . ' WHERE n.id = :id AND n.publicado = 1 AND n.activo = 1 LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function toggleActive(int $id, bool $active): bool
    {
        return $this->update($id, ['activo' => $active ? 1 : 0]);
    }

    public function togglePublished(int $id, bool $published): bool
    {
        return $this->update($id, ['publicado' => $published ? 1 : 0]);
    }
}
