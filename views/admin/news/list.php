<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$controller = new NewsController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'toggle_active') {
        $controller->toggleActive((int) $_POST['id']);
    } elseif (($_POST['action'] ?? '') === 'toggle_published') {
        $controller->togglePublished((int) $_POST['id']);
    } elseif (($_POST['action'] ?? '') === 'delete_news') {
        $controller->deleteNews((int) $_POST['id']);
    }
    redirectTo(BASE_URL . '/views/admin/news/list.php?' . http_build_query($_GET));
}

$filters = [
    'id_categoria' => Validator::sanitizeInt($_GET['id_categoria'] ?? ''),
    'fecha' => Validator::sanitizeString($_GET['fecha'] ?? ''),
    'publicado' => Validator::sanitizeString($_GET['publicado'] ?? ''),
    'buscar' => Validator::sanitizeString($_GET['buscar'] ?? ''),
];
$filters = array_filter($filters, fn($v) => $v !== '' && $v !== 0);

$page = (int) ($_GET['page'] ?? 1);
$result = $controller->listPaginated($filters, $page);
$categories = $controller->categories();

$pageTitle = 'Noticias';
$activeMenu = 'news';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.8rem;align-items:center">
        <h1 style="margin:0">Gestión de noticias</h1>
        <a href="<?= BASE_URL ?>/views/admin/news/form.php" class="btn">+ Nueva noticia</a>
    </div>

    <form method="get" action="<?= BASE_URL ?>/views/admin/news/list.php" class="filters-bar">
        <div class="field">
            <label>Buscar (título/autor)</label>
            <input type="search" name="buscar" value="<?= e($_GET['buscar'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Categoría</label>
            <select name="id_categoria">
                <option value="">Todas</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (($_GET['id_categoria'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?= e($_GET['fecha'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Estado</label>
            <select name="publicado">
                <option value="">Todos</option>
                <option value="1" <?= (($_GET['publicado'] ?? '') === '1') ? 'selected' : '' ?>>Publicado</option>
                <option value="0" <?= (($_GET['publicado'] ?? '') === '0') ? 'selected' : '' ?>>No publicado</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <a href="<?= BASE_URL ?>/views/admin/news/list.php" class="btn btn-secondary">Limpiar</a>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Miniatura</th><th>Título</th><th>Extracto</th><th>Categoría</th><th>Autor</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($result['items'])): ?>
                    <tr><td colspan="7">No se encontraron noticias.</td></tr>
                <?php endif; ?>
                <?php foreach ($result['items'] as $news): ?>
                    <tr>
                        <td data-label="Miniatura" class="thumb-cell">
                            <?php if (!empty($news['thumbnail'])): ?>
                                <img src="<?= UPLOAD_THUMB_URL . e(basename($news['thumbnail'])) ?>" alt="">
                            <?php endif; ?>
                        </td>
                        <td data-label="Título"><?= e($news['titulo']) ?></td>
                        <td data-label="Extracto"><?= e(truncate($news['contenido'], EXCERPT_LENGTH)) ?></td>
                        <td data-label="Categoría"><?= e($news['categoria_nombre']) ?></td>
                        <td data-label="Autor"><?= e($news['autor'] ?: $news['creador_nombre']) ?></td>
                        <td data-label="Estado">
                            <?php if ((int) $news['publicado'] === 1): ?>
                                <span class="badge badge-success">Publicado</span>
                            <?php else: ?>
                                <span class="badge badge-warning">No publicado</span>
                            <?php endif; ?>
                            <?php if ((int) $news['activo'] === 0): ?>
                                <span class="badge badge-danger">Dado de baja</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Acciones">
                            <?php $canModifyRow = $controller->canModify($news); ?>
                            <div class="actions">
                                <a class="btn btn-small" href="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= (int) $news['id'] ?>">Ver</a>
                                <?php if ($canModifyRow): ?>
                                    <a class="btn btn-small" href="<?= BASE_URL ?>/views/admin/news/form.php?id=<?= (int) $news['id'] ?>">Editar</a>
                                <?php endif; ?>
                                <?php if ($controller->isPrivileged()): ?>
                                    <form method="post" action="<?= BASE_URL ?>/views/admin/news/list.php?<?= http_build_query($_GET) ?>">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_published">
                                        <input type="hidden" name="id" value="<?= (int) $news['id'] ?>">
                                        <button type="submit" class="btn btn-small btn-secondary"><?= $news['publicado'] ? 'Despublicar' : 'Publicar' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canModifyRow): ?>
                                    <form method="post" action="<?= BASE_URL ?>/views/admin/news/list.php?<?= http_build_query($_GET) ?>" data-confirm="¿Cambiar el estado de esta noticia?">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?= (int) $news['id'] ?>">
                                        <button type="submit" class="btn btn-small <?= $news['activo'] ? 'btn-danger' : 'btn-success' ?>"><?= $news['activo'] ? 'Dar de baja' : 'Reactivar' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($controller->isPrivileged()): ?>
                                    <form method="post" action="<?= BASE_URL ?>/views/admin/news/list.php?<?= http_build_query($_GET) ?>" data-confirm="¿Eliminar esta noticia de forma PERMANENTE? Se borrarán también sus imágenes, comentarios y reacciones. Esta acción no se puede deshacer.">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action" value="delete_news">
                                        <input type="hidden" name="id" value="<?= (int) $news['id'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php $qs = $_GET; foreach (buildPaginationLinks($result['page'], $result['totalPages']) as $p): $qs['page'] = $p; ?>
            <?php if ($p === $result['page']): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="?<?= http_build_query($qs) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
