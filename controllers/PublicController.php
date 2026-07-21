<?php

/**
 * Controlador del sitio público: portada, listado completo y detalle
 * de noticias, además del conteo de visitantes.
 */
class PublicController extends BaseController
{
    private NewsModel $newsModel;
    private NewsImageModel $imageModel;
    private CategoryModel $categoryModel;
    private VisitModel $visitModel;

    public function __construct()
    {
        $this->newsModel = new NewsModel();
        $this->imageModel = new NewsImageModel();
        $this->categoryModel = new CategoryModel();
        $this->visitModel = new VisitModel();
    }

    public function registerVisit(): void
    {
        $this->visitModel->registerIfNew(session_id(), clientIp());
    }

    public function totalVisits(): int
    {
        return $this->visitModel->totalVisits();
    }

    /** Devuelve [noticiaPrincipal, [secundaria1, secundaria2]] para la portada. */
    public function homeHighlights(): array
    {
        $latest = $this->newsModel->latestPublished(3);

        foreach ($latest as &$item) {
            $item['thumbnail'] = $this->imageModel->firstThumbnail((int) $item['id']);
        }

        $main = $latest[0] ?? null;
        $secondary = array_slice($latest, 1, 2);

        return [$main, $secondary];
    }

    public function listPaginated(array $filters, int $page): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * NEWS_PER_PAGE_PUBLIC;

        $items = $this->newsModel->publicList($filters, NEWS_PER_PAGE_PUBLIC, $offset);
        $total = $this->newsModel->countPublicList($filters);

        foreach ($items as &$item) {
            $item['thumbnail'] = $this->imageModel->firstThumbnail((int) $item['id']);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / NEWS_PER_PAGE_PUBLIC)),
        ];
    }

    public function detail(int $id): ?array
    {
        $news = $this->newsModel->publicDetail($id);
        if ($news !== null) {
            $news['imagenes'] = $this->imageModel->byNews($id);
        }
        return $news;
    }

    public function categories(): array
    {
        return $this->categoryModel->allActive();
    }
}
