<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$controller = new NewsController();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$news = $id !== null ? $controller->findWithDetails($id) : null;

if ($id !== null && $news === null) {
    redirectTo(BASE_URL . '/views/admin/news/list.php');
}

if ($news !== null && !$controller->canModify($news)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'No tiene permisos para modificar esta noticia.'];
    redirectTo(BASE_URL . '/views/admin/news/list.php');
}

$isPrivileged = $controller->isPrivileged();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $controller->save($id);

    if (empty($errors)) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => $id === null ? 'Noticia creada correctamente.' : 'Noticia actualizada correctamente.'];
        redirectTo(BASE_URL . '/views/admin/news/list.php');
    }
}

$categories = $controller->categories();

$pageTitle = $id === null ? 'Nueva noticia' : 'Editar noticia';
$activeMenu = 'news';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <h1><?= e($pageTitle) ?></h1>

    <form method="post" action="<?= BASE_URL ?>/views/admin/news/form.php<?= $id !== null ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
        <?= Security::csrfField() ?>

        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" value="<?= e($_POST['titulo'] ?? $news['titulo'] ?? '') ?>" required>
            <?php if (!empty($errors['titulo'])): ?><div class="field-error"><?= e($errors['titulo']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="contenido">Contenido</label>
            <textarea id="contenido" name="contenido" style="min-height:220px" required><?= e($_POST['contenido'] ?? $news['contenido'] ?? '') ?></textarea>
            <?php if (!empty($errors['contenido'])): ?><div class="field-error"><?= e($errors['contenido']) ?></div><?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="autor">Autor (opcional)</label>
                <input type="text" id="autor" name="autor" value="<?= e($_POST['autor'] ?? $news['autor'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="video_url">Video (URL de YouTube/Vimeo, opcional)</label>
                <input type="url" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= e($_POST['video_url'] ?? $news['video_url'] ?? '') ?>">
                <?php if (!empty($errors['video_url'])): ?><div class="field-error"><?= e($errors['video_url']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="id_categoria">Categoría</label>
                <?php $catSel = $_POST['id_categoria'] ?? $news['id_categoria'] ?? ''; ?>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (string) $catSel === (string) $cat['id'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['id_categoria'])): ?><div class="field-error"><?= e($errors['id_categoria']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="publicado">Estado de publicación</label>
                <?php $pubSel = $_POST['publicado'] ?? ($news['publicado'] ?? 0); ?>
                <?php if ($isPrivileged): ?>
                    <select id="publicado" name="publicado">
                        <option value="1" <?= (string) $pubSel === '1' ? 'selected' : '' ?>>Publicado</option>
                        <option value="0" <?= (string) $pubSel === '0' ? 'selected' : '' ?>>No publicado</option>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="publicado" value="<?= (string) $pubSel ?>">
                    <p style="color:#6b7280;font-size:0.85rem;margin:0.4rem 0 0">
                        Solo un supervisor o administrador puede publicar. Estado actual:
                        <strong><?= (string) $pubSel === '1' ? 'Publicado' : 'No publicado' ?></strong>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Imágenes (mínimo <?= MIN_NEWS_IMAGES ?> en total; formatos jpg, png, webp)</label>

            <?php if ($news !== null && !empty($news['imagenes'])): ?>
                <p style="color:#6b7280;font-size:0.85rem">Imágenes actuales:</p>
                <div class="image-preview-grid">
                    <?php foreach ($news['imagenes'] as $img): ?>
                        <img src="<?= UPLOAD_THUMB_URL . e(basename($img['ruta_thumbnail'])) ?>" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <input type="file" name="imagenes[]" multiple accept="image/jpeg,image/png,image/webp" style="margin-top:0.6rem">
            <div id="image-preview" class="image-preview-grid"></div>
            <?php if (!empty($errors['imagenes'])): ?><div class="field-error"><?= e($errors['imagenes']) ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn">Guardar</button>
        <a href="<?= BASE_URL ?>/views/admin/news/list.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
