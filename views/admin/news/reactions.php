<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

$reactionController = new ReactionController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reactionId = (int) ($_POST['reaction_id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'approve':
            $reactionController->approve($reactionId);
            break;
        case 'reject':
            $reactionController->reject($reactionId);
            break;
        case 'delete':
            $reactionController->delete($reactionId);
            break;
    }
    redirectTo(BASE_URL . '/views/admin/news/reactions.php?' . http_build_query($_GET));
}

$filters = ['estado' => Validator::sanitizeString($_GET['estado'] ?? '')];
$filters = array_filter($filters, fn($v) => $v !== '');

$page = (int) ($_GET['page'] ?? 1);
$listado = $reactionController->listPaginated($filters, $page, 15);
$pendientes = $reactionController->pending();
$stats = $reactionController->stats();

$pageTitle = 'Reacciones';
$activeMenu = 'reactions';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <h1>Solicitudes de reacción pendientes (<?= count($pendientes) ?>)</h1>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Noticia</th><th>Tipo</th><th>IP</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($pendientes)): ?>
                    <tr><td colspan="5">No hay solicitudes pendientes por revisar.</td></tr>
                <?php endif; ?>
                <?php foreach ($pendientes as $r): ?>
                    <tr>
                        <td data-label="Noticia"><a href="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= (int) $r['id_noticia'] ?>"><?= e($r['noticia_titulo']) ?></a></td>
                        <td data-label="Tipo"><?= e(REACTION_TYPES[$r['tipo']]['label'] ?? $r['tipo']) ?></td>
                        <td data-label="IP"><?= e($r['ip_address']) ?></td>
                        <td data-label="Fecha"><?= formatDate($r['created_at']) ?></td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <form method="post" action="<?= BASE_URL ?>/views/admin/news/reactions.php?<?= http_build_query($_GET) ?>">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="reaction_id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-success">Aprobar</button>
                                </form>
                                <form method="post" action="<?= BASE_URL ?>/views/admin/news/reactions.php?<?= http_build_query($_GET) ?>">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="reaction_id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-secondary">Rechazar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h1>Estadísticas de reacciones aprobadas</h1>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Noticia</th><th>Total de reacciones</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($stats)): ?>
                    <tr><td colspan="3">Aún no hay reacciones aprobadas.</td></tr>
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

<div class="card">
    <h2>Historial completo de reacciones</h2>

    <form method="get" action="<?= BASE_URL ?>/views/admin/news/reactions.php" class="filters-bar">
        <div class="field">
            <label>Estado</label>
            <select name="estado">
                <option value="">Todos</option>
                <option value="pendiente" <?= (($_GET['estado'] ?? '') === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="aprobado" <?= (($_GET['estado'] ?? '') === 'aprobado') ? 'selected' : '' ?>>Aprobado</option>
                <option value="rechazado" <?= (($_GET['estado'] ?? '') === 'rechazado') ? 'selected' : '' ?>>Rechazado</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Noticia</th><th>Tipo</th><th>IP</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($listado['items'])): ?>
                    <tr><td colspan="6">No hay reacciones que coincidan con el filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($listado['items'] as $r): ?>
                    <?php $estadoBadge = ['pendiente' => 'badge-warning', 'aprobado' => 'badge-success', 'rechazado' => 'badge-danger']; ?>
                    <tr>
                        <td data-label="Noticia"><a href="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= (int) $r['id_noticia'] ?>"><?= e($r['noticia_titulo']) ?></a></td>
                        <td data-label="Tipo"><?= e(REACTION_TYPES[$r['tipo']]['label'] ?? $r['tipo']) ?></td>
                        <td data-label="IP"><?= e($r['ip_address']) ?></td>
                        <td data-label="Estado"><span class="badge <?= $estadoBadge[$r['estado']] ?? 'badge-muted' ?>"><?= e(ucfirst($r['estado'])) ?></span></td>
                        <td data-label="Fecha"><?= formatDate($r['created_at']) ?></td>
                        <td data-label="Acciones">
                            <form method="post" action="<?= BASE_URL ?>/views/admin/news/reactions.php?<?= http_build_query($_GET) ?>" data-confirm="¿Eliminar esta reacción?">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="reaction_id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
