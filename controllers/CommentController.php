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

    /**
     * Comentario enviado desde el sitio público (queda pendiente de
     * aprobación). Si hay una cuenta pública (rol "usuario") con sesión
     * iniciada, se usan su nombre/correo y se enlaza el comentario a esa
     * cuenta; de lo contrario funciona como comentario de invitado.
     */
    public function storePublic(int $newsId): array
    {
        $this->requireCsrf();

        $loggedAsPublicUser = isPublicUser();

        $nombre = $loggedAsPublicUser
            ? currentUserName()
            : Validator::sanitizeString($this->input('nombre_usuario'));
        $email = $loggedAsPublicUser
            ? ($_SESSION['user_email'] ?? '')
            : Validator::sanitizeEmail($this->input('email'));
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
            'id_usuario' => $loggedAsPublicUser ? (int) $_SESSION['user_id'] : null,
            'nombre_usuario' => $nombre,
            'email' => $email,
            'comentario' => $comentario,
            'estado' => 'pendiente',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [];
    }

    /** Aprobar/bloquear/eliminar están reservados a admin y supervisor. */
    private function requireModerator(): void
    {
        if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'supervisor'], true)) {
            http_response_code(403);
            die('Acceso denegado: no cuenta con los permisos necesarios.');
        }
    }

    public function approve(int $id): void
    {
        $this->requireCsrf();
        $this->requireModerator();
        $this->commentModel->updateStatus($id, 'aprobado');
    }

    public function block(int $id): void
    {
        $this->requireCsrf();
        $this->requireModerator();
        $this->commentModel->updateStatus($id, 'bloqueado');
    }

    public function delete(int $id): void
    {
        $this->requireCsrf();
        $this->requireModerator();
        $this->commentModel->delete($id);
    }

    /** Responder comentarios está reservado exclusivamente al rol admin. */
    public function reply(int $id, string $respuesta): void
    {
        $this->requireCsrf();
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            die('Acceso denegado: solo el administrador puede responder comentarios.');
        }

        $respuesta = Validator::sanitizeString($respuesta);
        if ($respuesta !== '') {
            $this->commentModel->reply($id, $respuesta, (int) $_SESSION['user_id']);
        }
    }
}
