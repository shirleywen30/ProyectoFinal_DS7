<?php

/**
 * Controlador de reacciones ("me gusta", etc.) públicas y su moderación.
 * Toda reacción enviada desde el sitio público queda como solicitud
 * "pendiente"; solo cuenta en los totales visibles una vez que un
 * administrador o supervisor la aprueba desde el panel.
 */
class ReactionController extends BaseController
{
    private ReactionModel $reactionModel;

    public function __construct()
    {
        $this->reactionModel = new ReactionModel();
    }

    /** Envía la solicitud de reacción de un visitante (queda pendiente de aprobación). */
    public function react(int $newsId, string $tipo): array
    {
        $this->requireCsrf();

        if (!array_key_exists($tipo, REACTION_TYPES)) {
            $tipo = 'like';
        }

        $userId = isLoggedIn() ? (int) $_SESSION['user_id'] : null;
        $added = $this->reactionModel->addReaction($newsId, clientIp(), $tipo, $userId);

        return [
            'added' => $added,
            'total' => $this->reactionModel->countByNews($newsId),
        ];
    }

    public function countByNews(int $newsId): int
    {
        return $this->reactionModel->countByNews($newsId);
    }

    public function countByNewsGroupedByType(int $newsId): array
    {
        return $this->reactionModel->countByNewsGroupedByType($newsId);
    }

    /** Estado (pendiente/aprobado) por cada tipo de reacción, para pintar los botones correctamente. */
    public function myStatusByType(int $newsId): array
    {
        $ip = clientIp();
        $result = [];
        foreach (array_keys(REACTION_TYPES) as $tipo) {
            $result[$tipo] = $this->reactionModel->myStatus($newsId, $ip, $tipo);
        }
        return $result;
    }

    public function alreadyReacted(int $newsId, ?string $tipo = null): bool
    {
        return $this->reactionModel->alreadyReacted($newsId, clientIp(), $tipo);
    }

    /** Estadísticas de reacciones (aprobadas) por noticia, para el panel administrativo. */
    public function stats(): array
    {
        $this->requireAuth();
        return $this->reactionModel->statsByNews();
    }

    /** Solicitudes pendientes de aprobación, para el panel administrativo. */
    public function pending(): array
    {
        $this->requireRole('admin', 'supervisor');
        return $this->reactionModel->pending();
    }

    public function listPaginated(array $filters, int $page, int $perPage = 15): array
    {
        $this->requireRole('admin', 'supervisor');

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $items = $this->reactionModel->filter($filters, $perPage, $offset);

        return [
            'items' => $items,
            'page'  => $page,
        ];
    }

    /** Aprobar/rechazar/eliminar reacciones están reservados a admin y supervisor. */
    public function approve(int $id): void
    {
        $this->requireCsrf();
        $this->requireRole('admin', 'supervisor');
        $this->reactionModel->updateStatus($id, 'aprobado');
    }

    public function reject(int $id): void
    {
        $this->requireCsrf();
        $this->requireRole('admin', 'supervisor');
        $this->reactionModel->updateStatus($id, 'rechazado');
    }

    public function delete(int $id): void
    {
        $this->requireCsrf();
        $this->requireRole('admin', 'supervisor');
        $this->reactionModel->delete($id);
    }
}
