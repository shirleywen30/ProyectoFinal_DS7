<?php

/**
 * Controlador CRUD de noticias: creación con imágenes múltiples y firma
 * digital de integridad (RNF-06), listado paginado con filtros/búsqueda,
 * y despublicación lógica (activo/publicado).
 */
class NewsController extends BaseController
{
    private NewsModel $newsModel;
    private NewsImageModel $imageModel;
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->newsModel = new NewsModel();
        $this->imageModel = new NewsImageModel();
        $this->categoryModel = new CategoryModel();
    }

    public function listPaginated(array $filters, int $page): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * NEWS_PER_PAGE_ADMIN;

        $items = $this->newsModel->filterAdmin($filters, NEWS_PER_PAGE_ADMIN, $offset);
        $total = $this->newsModel->countFilterAdmin($filters);

        foreach ($items as &$item) {
            $item['thumbnail'] = $this->imageModel->firstThumbnail((int) $item['id']);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / NEWS_PER_PAGE_ADMIN)),
        ];
    }

    public function findWithDetails(int $id): ?array
    {
        $news = $this->newsModel->findWithDetails($id);
        if ($news !== null) {
            $news['imagenes'] = $this->imageModel->byNews($id);
        }
        return $news;
    }

    public function categories(): array
    {
        return $this->categoryModel->allActive();
    }

    /** Devuelve arreglo de errores; vacío si la operación fue exitosa. */
    public function save(?int $id): array
    {
        $this->requireCsrf();

        $titulo = Validator::sanitizeString($this->input('titulo'));
        $contenido = Validator::sanitizeRichText($this->input('contenido'));
        $autor = Validator::sanitizeString($this->input('autor'));
        $idCategoria = Validator::sanitizeInt($this->input('id_categoria'));
        $publicado = $this->input('publicado', '0') === '1' ? 1 : 0;

        $validator = new Validator();
        $validator->isRequired($titulo, 'titulo', 'título')
            ->isLength($titulo, 'titulo', 'título', 5, 200)
            ->isRequired($contenido, 'contenido', 'contenido')
            ->isRequired($idCategoria, 'id_categoria', 'categoría');

        $uploader = new ImageUploader();
        $incomingFiles = $_FILES['imagenes'] ?? ['name' => []];
        $newFilesCount = count(array_filter($incomingFiles['name'] ?? [], fn($n) => $n !== ''));

        if ($id === null) {
            $existingCount = 0;
        } else {
            $existingCount = count($this->imageModel->byNews($id));
        }

        if (($existingCount + $newFilesCount) < MIN_NEWS_IMAGES) {
            $validator->addError('imagenes', 'La noticia debe tener al menos ' . MIN_NEWS_IMAGES . ' imágenes (actualmente tiene ' . $existingCount . ').');
        }

        if ($validator->fails()) {
            return $validator->getErrors();
        }

        $data = [
            'titulo' => $titulo,
            'contenido' => $contenido,
            'autor' => $autor !== '' ? $autor : null,
            'id_categoria' => $idCategoria,
            'publicado' => $publicado,
        ];

        if ($id === null) {
            $data['id_usuario'] = (int) $_SESSION['user_id'];
            $data['activo'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');

            $data['firma_digital'] = Security::generateSignature([
                $titulo, $contenido, $data['id_usuario'], $data['created_at'],
            ]);

            $newsId = $this->newsModel->create($data);
        } else {
            $newsId = $id;
            $original = $this->newsModel->find($id);

            $data['firma_digital'] = Security::generateSignature([
                $titulo, $contenido, $original['id_usuario'], $original['created_at'],
            ]);
            $data['updated_at'] = date('Y-m-d H:i:s');

            $this->newsModel->update($id, $data);
        }

        if ($newFilesCount > 0) {
            $uploaded = $uploader->processMultiple($incomingFiles);
            foreach ($uploaded as $order => $paths) {
                $this->imageModel->create([
                    'id_noticia' => $newsId,
                    'ruta_imagen' => $paths['ruta_imagen'],
                    'ruta_thumbnail' => $paths['ruta_thumbnail'],
                    'orden' => $existingCount + $order,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            if ($uploader->hasErrors()) {
                return ['imagenes' => implode(' ', $uploader->getErrors())];
            }
        }

        return [];
    }

    public function toggleActive(int $id): void
    {
        $this->requireCsrf();
        $news = $this->newsModel->find($id);
        if ($news !== null) {
            $this->newsModel->toggleActive($id, !((bool) $news['activo']));
        }
    }

    public function togglePublished(int $id): void
    {
        $this->requireCsrf();
        $news = $this->newsModel->find($id);
        if ($news !== null) {
            $this->newsModel->togglePublished($id, !((bool) $news['publicado']));
        }
    }

    /**
     * Verifica que la firma digital almacenada coincida con los campos
     * clave actuales de la noticia (detección de alteración de datos).
     */
    public function verifyIntegrity(array $news): bool
    {
        return Security::verifySignature(
            [$news['titulo'], $news['contenido'], $news['id_usuario'], $news['created_at']],
            $news['firma_digital'] ?? ''
        );
    }
}
