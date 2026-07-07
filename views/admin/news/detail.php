<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$newsController = new NewsController();
$commentController = new CommentController();
$reactionController = new ReactionController();

$id = (int) ($_GET['id'] ?? 0);
$news = $newsController->findWithDetails($id);

if ($news === null) {
    redirectTo(BASE_URL . '/views/admin/news/list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'approve':
            $commentController->approve($commentId);
            break;
        case 'block':
            $commentController->block($commentId);
            break;
        case 'delete':
            $commentController->delete($commentId);
            break;
        case 'reply':
            $commentController->reply($commentId, $_POST['respuesta'] ?? '');
            break;
    }
    redirectTo(BASE_URL . '/views/admin/news/detail.php?id=' . $id);
}

$comments = $commentController->byNews($id, null); // todos los estados para gestión
$reactionsTotal = $reactionController->countByNews($id);
$integrityOk = $newsController->verifyIntegrity($news);

$pageTitle = 'Detalle de noticia';
$activeMenu = 'news';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.8rem;align-items:center">
        <h1 style="margin:0"><?= e($news['titulo']) ?></h1>
        <a href="<?= BASE_URL ?>/views/admin/news/form.php?id=<?= $id ?>" class="btn btn-small">Editar</a>
    </div>

    <p class="news-meta" style="color:#6b7280">
        <?= e($news['categoria_nombre']) ?> &middot;
        <?= e($news['autor'] ?: $news['creador_nombre']) ?> &middot;
        <?= formatDate($news['created_at']) ?> &middot;
        &#10084; <?= $reactionsTotal ?> reacciones
    </p>

    <p>Integridad de la noticia:
        <?php if ($integrityOk): ?>
            <span class="integrity-ok">&#10003; Firma digital válida</span>
        <?php else: ?>
            <span class="integrity-fail">&#10007; La firma digital no coincide (posible alteración de datos)</span>
        <?php endif; ?>
    </p>

    <div class="detail-images">
        <?php foreach ($news['imagenes'] as $img): ?>
            <img src="<?= UPLOAD_NEWS_URL . e(basename($img['ruta_imagen'])) ?>" alt="">
        <?php endforeach; ?>
    </div>

    <div class="news-content"><?= $news['contenido'] ?></div>
</div>

<div class="card">
    <h2>Gestión de comentarios (<?= count($comments) ?>)</h2>

    <?php if (empty($comments)): ?>
        <p class="empty-state">Esta noticia aún no tiene comentarios.</p>
    <?php endif; ?>

    <?php foreach ($comments as $comment): ?>
        <div class="comment-item">
            <strong><?= e($comment['nombre_usuario']) ?></strong>
            <span class="comment-meta">(<?= e($comment['email']) ?>) &middot; <?= formatDate($comment['created_at']) ?>
                &middot;
                <?php
                $estadoBadge = ['pendiente' => 'badge-warning', 'aprobado' => 'badge-success', 'bloqueado' => 'badge-danger'];
                ?>
                <span class="badge <?= $estadoBadge[$comment['estado']] ?? 'badge-muted' ?>"><?= e(ucfirst($comment['estado'])) ?></span>
            </span>
            <p><?= e($comment['comentario']) ?></p>

            <?php if (!empty($comment['respuesta'])): ?>
                <div class="comment-reply"><strong>Respuesta del administrador:</strong> <?= e($comment['respuesta']) ?></div>
            <?php endif; ?>

            <div class="actions" style="margin-top:0.5rem">
                <?php if ($comment['estado'] !== 'aprobado'): ?>
                    <form method="post" action="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= $id ?>">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                        <button type="submit" class="btn btn-small btn-success">Aprobar</button>
                    </form>
                <?php endif; ?>
                <?php if ($comment['estado'] !== 'bloqueado'): ?>
                    <form method="post" action="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= $id ?>">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="block">
                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                        <button type="submit" class="btn btn-small btn-secondary">Bloquear</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= $id ?>" data-confirm="¿Eliminar este comentario?">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                    <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                </form>
            </div>

            <form method="post" action="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= $id ?>" style="margin-top:0.6rem;display:flex;gap:0.5rem">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                <input type="text" name="respuesta" placeholder="Responder a este comentario..." style="flex:1">
                <button type="submit" class="btn btn-small">Responder</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
