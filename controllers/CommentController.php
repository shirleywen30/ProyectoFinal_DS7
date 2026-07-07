<?php

/**
 * Controlador de comentarios: gestión administrativa (aprobar, bloquear,
 * eliminar, responder) y registro público de comentarios.
 */
class CommentController extends BaseController
{
    private CommentModel $commentModel;

    public function __construct(bool $requireAdmin = true)
    {
        if ($requireAdmin) {
            $this->requireAuth();
        }
        $this->commentModel = new CommentModel();
    }

    public function listPaginated(array $filters, int $page, int $perPage = 10): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $items = $this->commentModel->filter($filters, $perPage, $offset);
        $total = $this->commentModel->countFilter($filters);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function byNews(int $newsId, ?string $status = 'aprobado'): array
    {
        return $this->commentModel->byNews($newsId, $status);
    }

    /** Comentario enviado desde el sitio público (queda pendiente de aprobación). */
    public function storePublic(int $newsId): array
    {
        $this->requireCsrf();

        $nombre = Validator::sanitizeString($this->input('nombre_usuario'));
        $email = Validator::sanitizeEmail($this->input('email'));
        $comentario = Validator::sanitizeString($this->input('comentario'));

        $validator = new Validator();
        $validator->isRequired($nombre, 'nombre_usuario', 'nombre')
            ->isLength($nombre, 'nombre_usuario', 'nombre', 2, 100)
            ->isRequired($email, 'email', 'correo electrónico')
            ->isEmail($email, 'email', 'correo electrónico')
            ->isRequired($comentario, 'comentario', 'comentario')
            ->isLength($comentario, 'comentario', 'comentario', 3, 1000);

        if ($validator->fails()) {
            return $validator->getErrors();
        }

        $this->commentModel->create([
            'id_noticia' => $newsId,
            'nombre_usuario' => $nombre,
            'email' => $email,
            'comentario' => $comentario,
            'estado' => 'pendiente',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [];
    }

    public function approve(int $id): void
    {
        $this->requireCsrf();
        $this->commentModel->updateStatus($id, 'aprobado');
    }

    public function block(int $id): void
    {
        $this->requireCsrf();
        $this->commentModel->updateStatus($id, 'bloqueado');
    }

    public function delete(int $id): void
    {
        $this->requireCsrf();
        $this->commentModel->delete($id);
    }

    public function reply(int $id, string $respuesta): void
    {
        $this->requireCsrf();
        $respuesta = Validator::sanitizeString($respuesta);
        if ($respuesta !== '') {
            $this->commentModel->reply($id, $respuesta, (int) $_SESSION['user_id']);
        }
    }
}
