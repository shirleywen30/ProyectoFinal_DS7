<?php

/** Contador de visitantes del sitio público, con deduplicación por sesión. */
class VisitModel extends BaseModel
{
    protected string $table = 'visitas';

    public function registerIfNew(string $sessionId, string $ip): void
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM visitas WHERE session_id = :sid');
        $stmt->execute(['sid' => $sessionId]);

        if ((int) $stmt->fetch()['total'] === 0) {
            $this->create([
                'session_id' => $sessionId,
                'ip_address' => $ip,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function totalVisits(): int
    {
        return $this->count();
    }
}
