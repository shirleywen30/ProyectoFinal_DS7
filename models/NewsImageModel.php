<?php

/** Modelo de imágenes asociadas a una noticia (mínimo 3 por noticia). */
class NewsImageModel extends BaseModel
{
    protected string $table = 'noticia_imagenes';

    public function byNews(int $newsId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM noticia_imagenes WHERE id_noticia = :nid ORDER BY orden ASC');
        $stmt->execute(['nid' => $newsId]);
        return $stmt->fetchAll();
    }

    public function firstThumbnail(int $newsId): ?string
    {
        $stmt = $this->db->prepare('SELECT ruta_thumbnail FROM noticia_imagenes WHERE id_noticia = :nid ORDER BY orden ASC LIMIT 1');
        $stmt->execute(['nid' => $newsId]);
        $row = $stmt->fetch();
        return $row['ruta_thumbnail'] ?? null;
    }

    public function deleteByNews(int $newsId): bool
    {
        return $this->db->prepare('DELETE FROM noticia_imagenes WHERE id_noticia = :nid')->execute(['nid' => $newsId]);
    }
}
