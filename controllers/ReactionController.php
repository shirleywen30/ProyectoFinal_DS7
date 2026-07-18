<?php

/** Controlador de reacciones ("me gusta") públicas y estadísticas para admin. */
class ReactionController extends BaseController
{
    private ReactionModel $reactionModel;

    public function __construct()
    {
        $this->reactionModel = new ReactionModel();
    }

    public function react(int $newsId, string $tipo): array
    {
        $this->requireCsrf();

        if (!array_key_exists($tipo, REACTION_TYPES)) {
            $tipo = 'like';
        }

        $added = $this->reactionModel->addReaction($newsId, clientIp(), $tipo);

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

    public function alreadyReacted(int $newsId): bool
    {
        return $this->reactionModel->alreadyReacted($newsId, clientIp());
    }

    /** Estadísticas de reacciones por noticia, para el panel administrativo. */
    public function stats(): array
    {
        $this->requireAuth();
        return $this->reactionModel->statsByNews();
    }
}
