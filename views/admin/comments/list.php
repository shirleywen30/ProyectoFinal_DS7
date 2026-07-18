<?php
require_once __DIR__ . '/../../../config/bootstrap.php';

if (!isLoggedIn()) {
    redirectTo(BASE_URL . '/views/admin/login.php');
}

if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'supervisor'], true)) {
    http_response_code(403);
    die('Acceso denegado: no cuenta con los permisos necesarios.');
}

$controller = new CommentController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'approve':
            $controller->approve($commentId);
            break;
        case 'block':
            $controller->block($commentId);
            break;
        case 'delete':
            $controller->delete($commentId);
            break;
    }
    redirectTo(BASE_URL . '/views/admin/comments/list.php?' . http_build_query($_GET));
}

$filters = ['estado' => Validator::sanitizeString($_GET['estado'] ?? '')];
$filters = array_filter($filters, fn($v) => $v !== '');

$page = (int) ($_GET['page'] ?? 1);
$result = $controller->listPaginated($filters, $page, 10);

$pageTitle = 'Comentarios';
$activeMenu = 'comments';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<div class="card">
    <h1>Gestión de comentarios</h1>

    <form method="get" action="<?= BASE_URL ?>/views/admin/comments/list.php" class="filters-bar">
        <div class="field">
            <label>Estado</label>
            <select name="estado">
                <option value="">Todos</option>
                <option value="pendiente" <?= (($_GET['estado'] ?? '') === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="aprobado" <?= (($_GET['estado'] ?? '') === 'aprobado') ? 'selected' : '' ?>>Aprobado</option>
                <option value="bloqueado" <?= (($_GET['estado'] ?? '') === 'bloqueado') ? 'selected' : '' ?>>Bloqueado</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Noticia</th><th>Usuario</th><th>Comentario</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($result['items'])): ?>
                    <tr><td colspan="6">No hay comentarios que coincidan con el filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($result['items'] as $comment): ?>
                    <tr>
                        <td data-label="Noticia"><a href="<?= BASE_URL ?>/views/admin/news/detail.php?id=<?= (int) $comment['id_noticia'] ?>"><?= e($comment['noticia_titulo']) ?></a></td>
                        <td data-label="Usuario"><?= e($comment['nombre_usuario']) ?><br><small style="color:#6b7280"><?= e($comment['email']) ?></small></td>
                        <td data-label="Comentario"><?= e(truncate($comment['comentario'], 80)) ?></td>
                        <td data-label="Estado">
                            <?php
                            $estadoBadge = ['pendiente' => 'badge-warning', 'aprobado' => 'badge-success', 'bloqueado' => 'badge-danger'];
                            ?>
                            <span class="badge <?= $estadoBadge[$comment['estado']] ?? 'badge-muted' ?>"><?= e(ucfirst($comment['estado'])) ?></span>
                        </td>
                        <td data-label="Fecha"><?= formatDate($comment['created_at']) ?></td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <?php if ($comment['estado'] !== 'aprobado'): ?>
                                    <form method="post" action="<?= BASE_URL ?>/views/admin/comments/list.php?<?= http_build_query($_GET) ?>">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                        <button type="submit" class="btn btn-small btn-success">Aprobar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($comment['estado'] !== 'bloqueado'): ?>
                                    <form method="post" action="<?= BASE_URL ?>/views/admin/comments/list.php?<?= http_build_query($_GET) ?>">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action" value="block">
                                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                        <button type="submit" class="btn btn-small btn-secondary">Bloquear</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= BASE_URL ?>/views/admin/comments/list.php?<?= http_build_query($_GET) ?>" data-confirm="¿Eliminar este comentario?">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                                </form>
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
