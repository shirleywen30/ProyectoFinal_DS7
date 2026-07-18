<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$controller = new PublicController();
$controller->registerVisit();

$menuCategories = $controller->categories();
$totalVisits = $controller->totalVisits();

$pageTitle = 'Nosotros';
$activeMenu = 'about';
require ROOT_PATH . '/views/partials/public_header.php';
?>
<article class="news-detail">
    <h1 style="margin-top:0">Nosotros</h1>

    <section>
        <h2>¿Qué hace este sistema?</h2>
        <p>Sistema de Noticias es una plataforma web para la gestión y publicación de noticias organizadas por categoría (Deporte, Eventos, Tecnología, Política, Cultura). Permite a un equipo editorial crear, publicar y dar de baja noticias con imágenes y video, mientras el público puede leerlas, comentarlas y reaccionar a ellas. El panel administrativo ofrece además un tablero de estadísticas de uso del sitio.</p>
    </section>

    <section>
        <h2>Tecnologías utilizadas</h2>
        <p>El proyecto está construido con PHP nativo (arquitectura MVC propia, sin frameworks) y MySQL/MariaDB como motor de base de datos, sobre HTML, CSS y JavaScript sin dependencias externas. Incorpora buenas prácticas de seguridad (OWASP): protección CSRF, contraseñas con hash bcrypt, control de intentos de inicio de sesión y firma digital HMAC-SHA256 para verificar la integridad del contenido publicado.</p>
    </section>

    <section>
        <h2>Valor del proyecto y beneficios</h2>
        <ul>
            <li>Centraliza la publicación de noticias institucionales en un solo lugar, con control de roles (administrador/editor).</li>
            <li>Trazabilidad e integridad del contenido: cada noticia queda firmada digitalmente para detectar alteraciones.</li>
            <li>Panel de estadísticas con filtro por período para medir la actividad del sitio en el tiempo.</li>
            <li>Moderación de comentarios y sistema de reacciones para medir el interés del público.</li>
            <li>Bajo costo de infraestructura: no depende de servicios de terceros de pago para funcionar.</li>
        </ul>
    </section>

    <section>
        <h2>Modelo de negocio</h2>
        <p>Si este sistema llegara a comercializarse, el modelo propuesto es de licenciamiento a instituciones, medios locales o centros educativos: una tarifa de implementación más una cuota periódica de mantenimiento y soporte, con planes según volumen de noticias y usuarios administrativos.</p>
    </section>

    <section>
        <h2>Contacto</h2>
        <p>Proyecto académico desarrollado por:</p>
        <ul>
            <li>Jose Barahona</li>
            <li>Luis Jiménez</li>
            <li>Brian Lee</li>
            <li>Shirley Wen</li>
        </ul>
        <p>Universidad Tecnológica de Panamá &mdash; Licenciatura en Desarrollo y Gestión de Software.</p>
    </section>
</article>
<?php require ROOT_PATH . '/views/partials/public_footer.php'; ?>
