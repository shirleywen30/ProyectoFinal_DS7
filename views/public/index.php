<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$controller = new PublicController();
$controller->registerVisit();

[$main, $secondary] = $controller->homeHighlights();
$menuCategories = $controller->categories();
$totalVisits = $controller->totalVisits();

$pageTitle = 'Inicio';
$activeMenu = 'home';
require ROOT_PATH . '/views/partials/public_header.php';
?>
<h1 style="margin-top:0">Últimas noticias</h1>

<?php if ($main === null): ?>
    <p class="empty-state">Aún no hay noticias publicadas.</p>
<?php else: ?>
    <div class="home-grid">
        <article class="featured-news">
            <?php if (!empty($main['thumbnail'])): ?>
                <img src="<?= UPLOAD_THUMB_URL . e(basename($main['thumbnail'])) ?>" alt="<?= e($main['titulo']) ?>">
            <?php endif; ?>
            <div class="news-body">
                <span class="badge"><?= e($main['categoria_nombre']) ?></span>
                <h1><a href="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= (int) $main['id'] ?>"><?= e($main['titulo']) ?></a></h1>
                <p class="news-meta"><?= e($main['autor'] ?: $main['creador_nombre']) ?> &middot; <?= formatDate($main['created_at'], 'd/m/Y') ?></p>
                <p><?= e(truncate($main['contenido'], 220)) ?></p>
                <a href="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= (int) $main['id'] ?>" class="btn">Leer más</a>
            </div>
        </article>

        <div class="secondary-news-list">
            <?php foreach ($secondary as $item): ?>
                <article class="news-card">
                    <?php if (!empty($item['thumbnail'])): ?>
                        <img src="<?= UPLOAD_THUMB_URL . e(basename($item['thumbnail'])) ?>" alt="<?= e($item['titulo']) ?>">
                    <?php endif; ?>
                    <div class="news-body">
                        <span class="badge"><?= e($item['categoria_nombre']) ?></span>
                        <h3><a href="<?= BASE_URL ?>/views/public/news-detail.php?id=<?= (int) $item['id'] ?>"><?= e($item['titulo']) ?></a></h3>
                        <p class="news-meta"><?= formatDate($item['created_at'], 'd/m/Y') ?></p>
                        <p><?= e(truncate($item['contenido'], 100)) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<p style="margin-top:2rem;text-align:center">
    <a href="<?= BASE_URL ?>/views/public/all-news.php" class="btn">Ver todas las noticias</a>
</p>
<?php require ROOT_PATH . '/views/partials/public_footer.php'; ?>
