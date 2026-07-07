<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$publicController = new PublicController();
$commentController = new CommentController(false);
$reactionController = new ReactionController();

$publicController->registerVisit();

$id = (int) ($_GET['id'] ?? 0);
$news = $publicController->detail($id);

if ($news === null) {
    redirectTo(BASE_URL . '/views/public/all-news.php');
}

$commentErrors = [];
$commentSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'like') {
        $reactionController->like($id);
        redirectTo(BASE_URL . '/views/public/news-detail.php?id=' . $id . '#reacciones');
    }

    if (($_POST['action'] ?? '') === 'comment') {
        $commentErrors = $commentController->storePublic($id);
        if (empty($commentErrors)) {
            $commentSuccess = true;
        }
    }
}

$comments = $commentController->byNews($id, 'aprobado');
$reactionsTotal = $reactionController->countByNews($id);
$alreadyLiked = $reactionController->alreadyReacted($id);
$menuCategories = $publicController->categories();
$totalVisits = $publicController->totalVisits();

$pageTitle = $news['titulo'];
$activeMenu = 'news';
require ROOT_PATH . '/views/partials/public_header.php';
?>
<article class="news-detail">
    <span class="badge"><?= e($news['categoria_nombre']) ?></span>
    <h1><?= e($news['titulo']) ?></h1>
    <p class="news-meta"><?= e($news['autor'] ?: $news['creador_nombre']) ?> &middot; <?= formatDate($news['created_at']) ?></p>

    <?php if (!empty($news['imagenes'])): ?>
        <div class="news-gallery">
            <?php foreach ($news['imagenes'] as $img): ?>
                <img src="<?= UPLOAD_NEWS_URL . e(basename($img['ruta_imagen'])) ?>" alt="<?= e($news['titulo']) ?>">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="news-content"><?= $news['contenido'] ?></div>

    <div class="like-section" id="reacciones">
        <form method="post" action="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= $id ?>">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="like">
            <button type="submit" class="btn like-btn" <?= $alreadyLiked ? 'disabled' : '' ?>>
                &#10084; Me gusta
            </button>
        </form>
        <span><?= $reactionsTotal ?> persona(s) reaccionaron a esta noticia</span>
    </div>

    <section class="comments-section">
        <h2>Comentarios (<?= count($comments) ?>)</h2>

        <?php if ($commentSuccess): ?>
            <div class="alert alert-success">Su comentario fue enviado y quedará visible una vez sea aprobado por un administrador.</div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
            <p class="empty-state">Sé el primero en comentar esta noticia.</p>
        <?php endif; ?>

        <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
                <strong><?= e($comment['nombre_usuario']) ?></strong>
                <span class="comment-meta"><?= formatDate($comment['created_at']) ?></span>
                <p><?= e($comment['comentario']) ?></p>
                <?php if (!empty($comment['respuesta'])): ?>
                    <div class="comment-reply"><strong>Respuesta del administrador:</strong> <?= e($comment['respuesta']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <h3>Deja tu comentario</h3>
        <form method="post" action="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= $id ?>#comment-form" id="comment-form" class="comment-form">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="comment">

            <div>
                <input type="text" name="nombre_usuario" placeholder="Su nombre" value="<?= old('nombre_usuario') ?>" required>
                <?php if (!empty($commentErrors['nombre_usuario'])): ?><div class="field-error"><?= e($commentErrors['nombre_usuario']) ?></div><?php endif; ?>
            </div>
            <div>
                <input type="email" name="email" placeholder="Su correo electrónico" value="<?= old('email') ?>" required>
                <?php if (!empty($commentErrors['email'])): ?><div class="field-error"><?= e($commentErrors['email']) ?></div><?php endif; ?>
            </div>
            <div>
                <textarea name="comentario" placeholder="Escriba su comentario..." required><?= old('comentario') ?></textarea>
                <?php if (!empty($commentErrors['comentario'])): ?><div class="field-error"><?= e($commentErrors['comentario']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn">Publicar comentario</button>
        </form>
    </section>
</article>
<?php require ROOT_PATH . '/views/partials/public_footer.php'; ?>
