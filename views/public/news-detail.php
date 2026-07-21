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
    if (($_POST['action'] ?? '') === 'react') {
        $tipo = (string) ($_POST['tipo'] ?? 'like');
        $result = $reactionController->react($id, $tipo);

        if ($result['added']) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Su reacción fue enviada y quedará reflejada en el contador una vez sea aprobada por un administrador.'];
        }

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
$reactionCounts = $reactionController->countByNewsGroupedByType($id);
$myReactionStatus = $reactionController->myStatusByType($id); // ['like' => 'pendiente'|'aprobado'|null, ...]
$menuCategories = $publicController->categories();
$totalVisits = $publicController->totalVisits();
$loggedAsPublicUser = isPublicUser();

$pageTitle = $news['titulo'];
$activeMenu = 'news';
require ROOT_PATH . '/views/partials/public_header.php';
?>
<?php
    $heroImage = !empty($news['imagenes']) ? $news['imagenes'][0] : null;
    $galleryImages = !empty($news['imagenes']) ? array_slice($news['imagenes'], 1) : [];
    $embedUrl = embedVideoUrl($news['video_url'] ?? null);
?>
<article class="news-detail">
    <?php if ($heroImage !== null): ?>
        <div class="news-hero">
            <img src="<?= UPLOAD_NEWS_URL . e(basename($heroImage['ruta_imagen'])) ?>" alt="<?= e($news['titulo']) ?>">
        </div>
    <?php endif; ?>

    <span class="badge"><?= e($news['categoria_nombre']) ?></span>
    <h1><?= e($news['titulo']) ?></h1>
    <p class="news-meta">Publicado por: <strong><?= e($news['autor'] ?: $news['creador_nombre']) ?></strong> &middot; <?= formatDate($news['created_at']) ?></p>

    <?php if (!empty($galleryImages)): ?>
        <div class="news-gallery">
            <?php foreach ($galleryImages as $img): ?>
                <img src="<?= UPLOAD_NEWS_URL . e(basename($img['ruta_imagen'])) ?>" alt="<?= e($news['titulo']) ?>">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($embedUrl !== null): ?>
        <div class="news-video">
            <iframe src="<?= e($embedUrl) ?>" title="<?= e($news['titulo']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    <?php endif; ?>

    <div class="news-content"><?= $news['contenido'] ?></div>

    <div class="like-section" id="reacciones">
        <div class="reaction-group">
            <?php foreach (REACTION_TYPES as $tipo => $info): ?>
                <?php
                    $miEstado = $myReactionStatus[$tipo] ?? null; // null | 'pendiente' | 'aprobado' | 'rechazado'
                    $yaEnviada = $miEstado === 'pendiente' || $miEstado === 'aprobado';
                ?>
                <form method="post" action="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= $id ?>" style="display:inline-block">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="react">
                    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                    <button type="submit" class="btn reaction-btn" <?= $yaEnviada ? 'disabled' : '' ?>
                        title="<?= $miEstado === 'pendiente' ? 'Su reacción está pendiente de aprobación' : e($info['label']) ?>">
                        <?= $info['icon'] ?> <?= (int) ($reactionCounts[$tipo] ?? 0) ?>
                    </button>
                    <?php if ($miEstado === 'pendiente'): ?>
                        <div class="reaction-pending-note" style="font-size:0.75rem;color:#b45309">Pendiente de aprobación</div>
                    <?php endif; ?>
                </form>
            <?php endforeach; ?>
        </div>
        <span><?= array_sum($reactionCounts) ?> persona(s) reaccionaron a esta noticia</span>
    </div>

    <section class="comments-section">
        <h2>Comentarios (<?= count($comments) ?>)</h2>

        <?php if ($commentSuccess): ?>
            <div class="alert alert-success">Su comentario fue enviado y quedará visible una vez sea aprobado por un administrador.</div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
            <p class="empty-state">Sé el primero en comentar esta noticia.</p>
        <?php else: ?>
            <div class="comment-list">
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
            </div>
        <?php endif; ?>

        <div class="comment-form-wrapper">
            <h3>Deja tu comentario</h3>
            <?php if ($loggedAsPublicUser): ?>
                <p style="color:#6b7280;margin-top:-0.4rem">
                    Comentando como <strong><?= e(currentUserName()) ?></strong>
                    (<?= e($_SESSION['user_email'] ?? '') ?>)
                </p>
            <?php endif; ?>
            <form method="post" action="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= $id ?>#comment-form" id="comment-form" class="comment-form">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="comment">

                <?php if (!$loggedAsPublicUser): ?>
                    <div>
                        <input type="text" name="nombre_usuario" placeholder="Su nombre" value="<?= old('nombre_usuario') ?>" required>
                        <?php if (!empty($commentErrors['nombre_usuario'])): ?><div class="field-error"><?= e($commentErrors['nombre_usuario']) ?></div><?php endif; ?>
                    </div>
                    <div>
                        <input type="email" name="email" placeholder="Su correo electrónico" value="<?= old('email') ?>" required>
                        <?php if (!empty($commentErrors['email'])): ?><div class="field-error"><?= e($commentErrors['email']) ?></div><?php endif; ?>
                    </div>
                    <p style="font-size:0.85rem;color:#6b7280">
                        ¿Ya tiene cuenta? <a href="<?= BASE_URL ?>/views/admin/login.php">Inicie sesión</a> para comentar más rápido.
                    </p>
                <?php endif; ?>
                <div>
                    <textarea name="comentario" placeholder="Escriba su comentario..." required><?= old('comentario') ?></textarea>
                    <?php if (!empty($commentErrors['comentario'])): ?><div class="field-error"><?= e($commentErrors['comentario']) ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn">Publicar comentario</button>
            </form>
        </div>
    </section>
</article>
<?php require ROOT_PATH . '/views/partials/public_footer.php'; ?>
