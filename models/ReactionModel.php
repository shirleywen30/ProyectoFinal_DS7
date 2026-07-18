<?php

/** Reacciones ("me gusta") de usuarios públicos sobre noticias. */
class ReactionModel extends BaseModel
{
    protected string $table = 'reacciones';

    /** Registra una reacción (de cualquier tipo disponible), evitando duplicados por IP + noticia. */
    public function addReaction(int $newsId, string $ip, string $tipo): bool
    {
        if ($this->alreadyReacted($newsId, $ip)) {
            return false;
        }

        $this->create([
            'id_noticia' => $newsId,
            'tipo'       => $tipo,
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function alreadyReacted(int $newsId, string $ip): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM reacciones WHERE id_noticia = :nid AND ip_address = :ip');
        $stmt->execute(['nid' => $newsId, 'ip' => $ip]);
        return (int) $stmt->fetch()['total'] > 0;
    }

    public function countByNews(int $newsId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM reacciones WHERE id_noticia = :nid');
        $stmt->execute(['nid' => $newsId]);
        return (int) $stmt->fetch()['total'];
    }

    /** Conteo de reacciones de una noticia, agrupado por tipo (ej. ['like' => 3, 'eco' => 1]). */
    public function countByNewsGroupedByType(int $newsId): array
    {
        $stmt = $this->db->prepare('SELECT tipo, COUNT(*) AS total FROM reacciones WHERE id_noticia = :nid GROUP BY tipo');
        $stmt->execute(['nid' => $newsId]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['tipo']] = (int) $row['total'];
        }
        return $counts;
    }

    /** Estadísticas de reacciones agrupadas por noticia, para el panel admin. */
    public function statsByNews(): array
    {
        $sql = 'SELECT n.id, n.titulo, COUNT(r.id) AS total_reacciones
                FROM noticias n
                LEFT JOIN reacciones r ON r.id_noticia = n.id
                GROUP BY n.id, n.titulo
                ORDER BY total_reacciones DESC';
        return $this->db->query($sql)->fetchAll();
    }
}
