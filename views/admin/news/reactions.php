<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$reactionController = new ReactionController();
$stats = $reactionController->stats();

$pageTitle = 'Estadísticas de reacciones';
$activeMenu = 'reactions';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <h1>Estadísticas de reacciones ("Me gusta")</h1>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Noticia</th><th>Total de reacciones</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($stats)): ?>
                    <tr><td colspan="3">Aún no hay reacciones registradas.</td></tr>
                <?php endif; ?>
                <?php foreach ($stats as $row): ?>
                    <tr>
                        <td data-label="Noticia"><?= e($row['titulo']) ?></td>
                        <td data-label="Total">
                            &#10084; <?= (int) $row['total_reacciones'] ?>
                        </td>
                        <td data-label="Acciones">
                            <a class="btn btn-small" href="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= (int) $row['id'] ?>">Ver noticia</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
