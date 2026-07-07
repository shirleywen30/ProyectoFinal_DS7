<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$controller = new PublicController();
$controller->registerVisit();

$filters = [
    'id_categoria' => Validator::sanitizeInt($_GET['categoria'] ?? ''),
    'buscar' => Validator::sanitizeString($_GET['buscar'] ?? ''),
];
$filters = array_filter($filters, fn($v) => $v !== '' && $v !== 0);

$page = (int) ($_GET['page'] ?? 1);
$result = $controller->listPaginated($filters, $page);
$menuCategories = $controller->categories();
$totalVisits = $controller->totalVisits();

$pageTitle = 'Todas las noticias';
$activeMenu = 'news';
require ROOT_PATH . '/views/partials/public_header.php';
?>
<h1 style="margin-top:0">Todas las noticias</h1>

<form method="get" action="<?= BASE_URL ?>/views/public/all-news.php" class="filters-bar">
    <div class="field">
        <label>Buscar por título</label>
        <input type="search" name="buscar" value="<?= e($_GET['buscar'] ?? '') ?>">
    </div>
    <div class="field">
        <label>Categoría</label>
        <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($menuCategories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= (($_GET['categoria'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn">Buscar</button>
</form>

<?php if (empty($result['items'])): ?>
    <p class="empty-state">No se encontraron noticias con los criterios seleccionados.</p>
<?php else: ?>
    <div class="news-grid">
        <?php foreach ($result['items'] as $item): ?>
            <article class="news-card">
                <?php if (!empty($item['thumbnail'])): ?>
                    <img src="<?= UPLOAD_THUMB_URL . e(basename($item['thumbnail'])) ?>" alt="<?= e($item['titulo']) ?>">
                <?php endif; ?>
                <div class="news-body">
                    <span class="badge"><?= e($item['categoria_nombre']) ?></span>
                    <h3><a href="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= (int) $item['id'] ?>"><?= e($item['titulo']) ?></a></h3>
                    <p class="news-meta"><?= e($item['autor'] ?: $item['creador_nombre']) ?> &middot; <?= formatDate($item['created_at'], 'd/m/Y') ?></p>
                    <p><?= e(truncate($item['contenido'], 100)) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
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
<?php endif; ?>
<?php require ROOT_PATH . '/views/partials/public_footer.php'; ?>
