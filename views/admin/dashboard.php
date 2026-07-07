<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isLoggedIn()) {
    redirectTo(BASE_URL . '/views/admin/login.php');
}

$newsModel = new NewsModel();
$userModel = new UserModel();
$categoryModel = new CategoryModel();
$commentModel = new CommentModel();
$visitModel = new VisitModel();
$logModel = new LogModel();

$stats = [
    'noticias' => $newsModel->count(),
    'usuarios' => $userModel->count(),
    'categorias' => $categoryModel->count(),
    'comentarios_pendientes' => $commentModel->countFilter(['estado' => 'pendiente']),
    'visitas' => $visitModel->totalVisits(),
];

$recentLogs = $logModel->recent(10);

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<h1>Panel de administración</h1>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['noticias'] ?></div>
        <div class="stat-label">Noticias registradas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['usuarios'] ?></div>
        <div class="stat-label">Usuarios</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['categorias'] ?></div>
        <div class="stat-label">Categorías</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['comentarios_pendientes'] ?></div>
        <div class="stat-label">Comentarios pendientes</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['visitas'] ?></div>
        <div class="stat-label">Visitantes totales</div>
    </div>
</div>

<div class="card" style="margin-top:1.5rem">
    <h2>Últimos intentos de inicio de sesión</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Usuario</th><th>IP</th><th>Fecha y hora</th><th>Resultado</th></tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="4">Sin registros aún.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td data-label="Usuario"><?= e($log['usuario']) ?></td>
                        <td data-label="IP"><?= e($log['ip_address']) ?></td>
                        <td data-label="Fecha"><?= formatDate($log['fecha_hora']) ?></td>
                        <td data-label="Resultado">
                            <?php if ((int) $log['exito'] === 1): ?>
                                <span class="badge badge-success">Éxito</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Fallido</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_PATH . '/views/partials/admin_footer.php'; ?>
