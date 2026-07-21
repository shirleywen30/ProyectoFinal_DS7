<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isStaff()) {
    redirectTo(BASE_URL . '/views/admin/login.php');
}

$newsModel = new NewsModel();
$userModel = new UserModel();
$categoryModel = new CategoryModel();
$commentModel = new CommentModel();
$visitModel = new VisitModel();
$logModel = new LogModel();

$periodos = [
    '7' => 'Últimos 7 días',
    '30' => 'Último mes',
    '90' => 'Últimos 3 meses',
    '180' => 'Últimos 6 meses',
    '365' => 'Último año',
    'todo' => 'Todo el tiempo',
];
$periodo = $_GET['periodo'] ?? '30';
if (!array_key_exists($periodo, $periodos)) {
    $periodo = '30';
}
$from = $periodo === 'todo' ? null : date('Y-m-d H:i:s', strtotime("-{$periodo} days"));

$stats = [
    'noticias' => $newsModel->countSince('created_at', $from),
    'usuarios' => $userModel->countSince('created_at', $from),
    'categorias' => $categoryModel->count(),
    'comentarios_pendientes' => $commentModel->countFilter(['estado' => 'pendiente', 'desde' => $from]),
    'visitas' => $visitModel->countSince('created_at', $from),
];

$recentLogs = $logModel->recent(10);

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require ROOT_PATH . '/views/partials/admin_header.php';
require ROOT_PATH . '/views/partials/admin_menu.php';
?>
<h1>Panel de administración</h1>

<form method="get" action="<?= BASE_URL ?>/views/admin/dashboard.php" class="filters-bar">
    <div class="field">
        <label for="periodo">Período de las estadísticas</label>
        <select id="periodo" name="periodo" onchange="this.form.submit()">
            <?php foreach ($periodos as $value => $label): ?>
                <option value="<?= e((string) $value) ?>" <?= $periodo === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <noscript><button type="submit" class="btn btn-secondary">Filtrar</button></noscript>
</form>

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
