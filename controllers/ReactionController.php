<?php

/** Controlador de reacciones ("me gusta") públicas y estadísticas para admin. */
class ReactionController extends BaseController
{
    private ReactionModel $reactionModel;

    public function __construct()
    {
        $this->reactionModel = new ReactionModel();
    }

    public function like(int $newsId): array
    {
        $this->requireCsrf();
        $added = $this->reactionModel->addLike($newsId, clientIp());

        return [
            'added' => $added,
            'total' => $this->reactionModel->countByNews($newsId),
        ];
    }

    public function countByNews(int $newsId): int
    {
        return $this->reactionModel->countByNews($newsId);
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
