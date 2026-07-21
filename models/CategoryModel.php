<?php

/** Modelo de categorías de noticias (Deporte, Eventos, Tecnología, Política, Cultura...). */
class CategoryModel extends BaseModel
{
    protected string $table = 'categorias';

    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    public function nameExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM categorias WHERE nombre = :nombre';
        $params = ['nombre' => $nombre];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    public function hasNews(int $categoryId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM noticias WHERE id_categoria = :id');
        $stmt->execute(['id' => $categoryId]);
        return (int) $stmt->fetch()['total'] > 0;
    }
}
