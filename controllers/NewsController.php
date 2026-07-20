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

    /** Rol con permisos amplios sobre noticias (crear/modificar/publicar cualquiera). */
    public function isPrivileged(): bool
    {
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'supervisor'], true);
    }

    /** Un privilegiado puede modificar cualquier noticia; un editor, solo las suyas. */
    public function canModify(array $news): bool
    {
        return $this->isPrivileged() || (int) $news['id_usuario'] === (int) ($_SESSION['user_id'] ?? 0);
    }

    /** Devuelve arreglo de errores; vacío si la operación fue exitosa. */
    public function save(?int $id): array
    {
        $this->requireCsrf();

        $original = null;
        if ($id !== null) {
            $original = $this->newsModel->find($id);
            if ($original === null || !$this->canModify($original)) {
                http_response_code(403);
                die('Acceso denegado: no cuenta con los permisos necesarios para modificar esta noticia.');
            }
        }

        $titulo = Validator::sanitizeString($this->input('titulo'));
        $contenido = Validator::sanitizeRichText($this->input('contenido'));
        $autor = Validator::sanitizeString($this->input('autor'));
        $videoUrl = Validator::sanitizeString($this->input('video_url'));
        $idCategoria = Validator::sanitizeInt($this->input('id_categoria'));

        $publicadoInput = $this->input('publicado', '0') === '1' ? 1 : 0;
        // Un editor no puede fijar el estado de publicación: en creación queda
        // sin publicar y en edición se conserva el valor ya almacenado.
        $publicado = $this->isPrivileged() ? $publicadoInput : ($original !== null ? (int) $original['publicado'] : 0);

        $validator = new Validator();
        $validator->isRequired($titulo, 'titulo', 'título')
            ->isLength($titulo, 'titulo', 'título', 5, 200)
            ->isRequired($contenido, 'contenido', 'contenido')
            ->isRequired($idCategoria, 'id_categoria', 'categoría');

        if ($videoUrl !== '' && filter_var($videoUrl, FILTER_VALIDATE_URL) === false) {
            $validator->addError('video_url', 'El enlace del video no es una URL válida.');
        }

        $uploader = new ImageUploader();
        $incomingFiles = $_FILES['imagenes'] ?? ['name' => []];
        $newFilesCount = count(array_filter($incomingFiles['name'] ?? [], fn($n) => $n !== ''));

        $existingImages = $id === null ? [] : $this->imageModel->byNews($id);
        $existingCount = count($existingImages);

        // Imágenes existentes marcadas para eliminar; se filtran contra las
        // reales de esta noticia para evitar borrar imágenes de terceros.
        $imagesToDelete = [];
        if ($id !== null) {
            $requestedDeleteIds = array_map('intval', (array) $this->input('eliminar_imagenes', []));
            foreach ($existingImages as $img) {
                if (in_array((int) $img['id'], $requestedDeleteIds, true)) {
                    $imagesToDelete[] = $img;
                }
            }
        }
        $deleteIds = array_map(fn($img) => (int) $img['id'], $imagesToDelete);
        $remainingImages = array_values(array_filter(
            $existingImages,
            fn($img) => !in_array((int) $img['id'], $deleteIds, true)
        ));
        $remainingCount = count($remainingImages);

        // Imagen existente elegida como miniatura principal (solo válida si
        // sigue entre las que no se eliminaron en este mismo guardado).
        $portadaId = $id !== null ? (int) $this->input('imagen_portada', 0) : 0;

        if (($remainingCount + $newFilesCount) < MIN_NEWS_IMAGES) {
            $validator->addError('imagenes', 'La noticia debe tener al menos ' . MIN_NEWS_IMAGES . ' imágenes (quedarían ' . $remainingCount . ').');
        }

        if ($validator->fails()) {
            return $validator->getErrors();
        }

        $data = [
            'titulo' => $titulo,
            'contenido' => $contenido,
            'autor' => $autor !== '' ? $autor : null,
            'video_url' => $videoUrl !== '' ? $videoUrl : null,
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

            $data['firma_digital'] = Security::generateSignature([
                $titulo, $contenido, $original['id_usuario'], $original['created_at'],
            ]);
            $data['updated_at'] = date('Y-m-d H:i:s');

            $this->newsModel->update($id, $data);
        }

        foreach ($imagesToDelete as $img) {
            $imagePath = UPLOAD_NEWS_PATH . basename($img['ruta_imagen']);
            $thumbPath = UPLOAD_THUMB_PATH . basename($img['ruta_thumbnail']);

            if (is_file($imagePath)) {
                @unlink($imagePath);
            }
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }

            $this->imageModel->delete((int) $img['id']);
        }

        $portadaIndex = null;
        foreach ($remainingImages as $index => $img) {
            if ((int) $img['id'] === $portadaId) {
                $portadaIndex = $index;
                break;
            }
        }

        if ($portadaIndex !== null && $portadaIndex !== 0) {
            $reordered = $remainingImages;
            $selected = array_splice($reordered, $portadaIndex, 1)[0];
            array_unshift($reordered, $selected);

            foreach ($reordered as $newOrder => $img) {
                $this->imageModel->update((int) $img['id'], ['orden' => $newOrder]);
            }
        }

        if ($newFilesCount > 0) {
            $uploaded = $uploader->processMultiple($incomingFiles);
            foreach ($uploaded as $order => $paths) {
                $this->imageModel->create([
                    'id_noticia' => $newsId,
                    'ruta_imagen' => $paths['ruta_imagen'],
                    'ruta_thumbnail' => $paths['ruta_thumbnail'],
                    'orden' => $remainingCount + $order,
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
        if ($news !== null && $this->canModify($news)) {
            $this->newsModel->toggleActive($id, !((bool) $news['activo']));
        }
    }

    public function togglePublished(int $id): void
    {
        $this->requireCsrf();
        if (!$this->isPrivileged()) {
            http_response_code(403);
            die('Acceso denegado: solo un supervisor o administrador puede publicar noticias.');
        }
        $news = $this->newsModel->find($id);
        if ($news !== null) {
            $this->newsModel->togglePublished($id, !((bool) $news['publicado']));
        }
    }

    /**
     * Elimina una noticia de forma permanente: borra sus imágenes físicas
     * del disco y la fila de la noticia (comentarios, reacciones e imágenes
     * en base de datos se eliminan en cascada por las llaves foráneas).
     * Distinto de toggleActive() (baja lógica, reversible).
     */
    public function deleteNews(int $id): void
    {
        $this->requireCsrf();

        if (!$this->isPrivileged()) {
            http_response_code(403);
            die('Acceso denegado: solo un supervisor o administrador puede eliminar noticias.');
        }

        $news = $this->newsModel->find($id);
        if ($news === null) {
            return;
        }

        foreach ($this->imageModel->byNews($id) as $img) {
            $imagePath = UPLOAD_NEWS_PATH . basename($img['ruta_imagen']);
            $thumbPath = UPLOAD_THUMB_PATH . basename($img['ruta_thumbnail']);

            if (is_file($imagePath)) {
                @unlink($imagePath);
            }
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        $this->newsModel->delete($id);
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
