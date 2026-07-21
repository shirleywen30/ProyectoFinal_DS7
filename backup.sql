-- =============================================================================
-- Sistema de Noticias - Script de base de datos
-- Motor: MySQL 5.7+ / MariaDB (compatible con WAMP64)
-- Incluye: estructura completa, índices, llaves foráneas y datos de prueba.
--
-- Uso: importar este archivo completo desde phpMyAdmin o:
--   mysql -u root -p < backup.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS sistema_noticias;
CREATE DATABASE sistema_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_noticias;

-- -----------------------------------------------------------------------------
-- Tabla: usuarios
-- Usuarios administrativos (admin/editor/supervisor) Y cuentas públicas
-- normales (rol "usuario", creadas desde el registro público del sitio).
-- Si ya tiene la base de datos importada de una versión anterior, ejecute:
--   ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin','editor','supervisor','usuario') NOT NULL DEFAULT 'usuario';
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre            VARCHAR(100)        NOT NULL,
    email             VARCHAR(150)        NOT NULL,
    password          VARCHAR(255)        NOT NULL COMMENT 'Hash bcrypt (password_hash)',
    rol               ENUM('admin','editor','supervisor','usuario') NOT NULL DEFAULT 'usuario',
    activo            TINYINT(1)          NOT NULL DEFAULT 1,
    intentos_fallidos INT UNSIGNED        NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME            NULL,
    created_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME            NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: categorias
-- -----------------------------------------------------------------------------
CREATE TABLE categorias (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60)  NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: noticias
-- firma_digital: HMAC-SHA256 sobre (titulo|contenido|id_usuario|created_at)
-- usado para verificar la integridad del contenido (RNF-06).
-- Si ya tiene la base de datos importada de una versión anterior (sin la
-- columna video_url), ejecute en su lugar:
--   ALTER TABLE noticias ADD COLUMN video_url VARCHAR(255) NULL AFTER autor;
-- -----------------------------------------------------------------------------
CREATE TABLE noticias (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo         VARCHAR(200) NOT NULL,
    contenido      TEXT         NOT NULL,
    id_usuario     INT UNSIGNED NOT NULL COMMENT 'Usuario que creó la noticia',
    autor          VARCHAR(120) NULL COMMENT 'Autor visible (opcional, distinto del usuario del sistema)',
    video_url      VARCHAR(255) NULL COMMENT 'Enlace embebible de YouTube/Vimeo (opcional)',
    id_categoria   INT UNSIGNED NOT NULL,
    publicado      TINYINT(1)   NOT NULL DEFAULT 0,
    activo         TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Baja lógica',
    firma_digital  VARCHAR(64)  NOT NULL COMMENT 'HMAC-SHA256 de integridad',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_noticias_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_noticias_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE RESTRICT,
    KEY idx_noticias_publicado_activo (publicado, activo),
    KEY idx_noticias_categoria (id_categoria),
    KEY idx_noticias_fecha (created_at),
    FULLTEXT KEY ftx_noticias_titulo (titulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: noticia_imagenes
-- Cada noticia debe tener como mínimo 3 imágenes (regla aplicada en la app).
-- -----------------------------------------------------------------------------
CREATE TABLE noticia_imagenes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_noticia      INT UNSIGNED NOT NULL,
    ruta_imagen     VARCHAR(255) NOT NULL COMMENT 'Ruta relativa dentro de /public/uploads/',
    ruta_thumbnail  VARCHAR(255) NOT NULL,
    orden           INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_imagenes_noticia FOREIGN KEY (id_noticia) REFERENCES noticias(id) ON DELETE CASCADE,
    KEY idx_imagenes_noticia (id_noticia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: comentarios
-- -----------------------------------------------------------------------------
CREATE TABLE comentarios (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_noticia        INT UNSIGNED NOT NULL,
    id_usuario        INT UNSIGNED NULL COMMENT 'Cuenta pública (rol usuario) que comentó, si inició sesión',
    nombre_usuario    VARCHAR(100) NOT NULL,
    email             VARCHAR(150) NOT NULL,
    comentario        TEXT         NOT NULL,
    estado            ENUM('pendiente','aprobado','bloqueado') NOT NULL DEFAULT 'pendiente',
    respuesta         TEXT         NULL COMMENT 'Respuesta del administrador',
    id_usuario_admin  INT UNSIGNED NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comentarios_noticia FOREIGN KEY (id_noticia) REFERENCES noticias(id) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_comentarios_admin FOREIGN KEY (id_usuario_admin) REFERENCES usuarios(id) ON DELETE SET NULL,
    KEY idx_comentarios_estado (estado),
    KEY idx_comentarios_noticia (id_noticia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: reacciones ("me gusta", etc.), únicas por IP + noticia + tipo.
-- Toda reacción nace en estado "pendiente" y solo se refleja en los conteos
-- públicos una vez que un administrador/supervisor la aprueba.
-- -----------------------------------------------------------------------------
CREATE TABLE reacciones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_noticia  INT UNSIGNED NOT NULL,
    id_usuario  INT UNSIGNED NULL COMMENT 'Cuenta pública (rol usuario) que reaccionó, si inició sesión',
    tipo        VARCHAR(20)  NOT NULL DEFAULT 'like',
    ip_address  VARCHAR(45)  NOT NULL,
    estado      ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reacciones_noticia FOREIGN KEY (id_noticia) REFERENCES noticias(id) ON DELETE CASCADE,
    CONSTRAINT fk_reacciones_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uq_reaccion_ip_noticia_tipo (id_noticia, ip_address, tipo),
    KEY idx_reacciones_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: login_logs
-- Registro de todos los intentos de inicio de sesión (RNF-04 / OWASP).
-- -----------------------------------------------------------------------------
CREATE TABLE login_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario     VARCHAR(150) NOT NULL COMMENT 'Usuario o correo ingresado',
    ip_address  VARCHAR(45)  NOT NULL,
    exito       TINYINT(1)   NOT NULL,
    user_agent  VARCHAR(255) NULL,
    fecha_hora  DATETIME     NOT NULL,
    KEY idx_login_logs_usuario (usuario),
    KEY idx_login_logs_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: visitas
-- Contador de visitantes del sitio público (deduplicado por sesión).
-- -----------------------------------------------------------------------------
CREATE TABLE visitas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id  VARCHAR(128) NOT NULL,
    ip_address  VARCHAR(45)  NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_visitas_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DATOS DE PRUEBA
-- =============================================================================

-- Usuarios: admin@hotmail.com / admin123  |  editor@hotmail.com / editor123  |  supervisor@hotmail.com / super123
-- Las contraseñas están almacenadas con password_hash() (bcrypt, cost 12).
INSERT INTO usuarios (id, nombre, email, password, rol, activo, intentos_fallidos, created_at) VALUES
(1, 'admin', 'admin@hotmail.com', '$2y$12$2R6Q0ZoLRvHIu1LgP6Be0u.5PNY10PNhb8qeBJDv8p1J0BOw.ooHa', 'admin', 1, 0, NOW()),
(2, 'Editor Demo', 'editor@hotmail.com', '$2y$12$MIqyu1vJobWc0Qlrzath8OF9Al4IjanbpEkAvuwzd3xQwtl660LQ6', 'editor', 1, 0, NOW()),
(3, 'Supervisor Demo', 'supervisor@hotmail.com', '$2y$12$TyRuZQlFAiAUHlIyGrjXDezzLUyE6/GojyyDvYj7O3uORbVuAM5Ry', 'supervisor', 1, 0, NOW());

INSERT INTO categorias (id, nombre, descripcion, activo, created_at) VALUES
(1, 'Deporte',     'Noticias relacionadas con deportes locales e internacionales.', 1, NOW()),
(2, 'Eventos',      'Eventos culturales, sociales y comunitarios.', 1, NOW()),
(3, 'Tecnología',   'Avances, lanzamientos y tendencias tecnológicas.', 1, NOW()),
(4, 'Política',     'Noticias sobre gobierno, congreso y política nacional.', 1, NOW()),
(5, 'Cultura',      'Arte, música, tradiciones y patrimonio cultural.', 1, NOW());

-- Noticias de prueba (firma_digital generada con HMAC-SHA256 sobre los
-- campos titulo|contenido|id_usuario|created_at, usando la misma clave
-- definida en config/config.php -> SIGNATURE_SECRET_KEY).
INSERT INTO noticias (id, titulo, contenido, id_usuario, autor, id_categoria, publicado, activo, firma_digital, created_at, updated_at) VALUES
(1, 'Selección nacional clasifica al mundial tras vibrante victoria', '<p>La selección nacional de fútbol logró anoche su clasificación al mundial luego de una vibrante victoria por 2 a 1 disputada ante una multitud que colmó el estadio principal.</p><p>El técnico destacó el esfuerzo colectivo del equipo y aseguró que el objetivo ahora es preparar de la mejor manera posible la fase final del torneo.</p><p>Miles de aficionados celebraron en las calles la clasificación, que no se lograba desde hace más de una década.</p>', 1, NULL, 1, 1, 1, '73deb67e3d09548cd5785ab4b2a2b4ea478e4a366d5fdbbf398de5978c7de66a', '2026-07-01 09:15:00', '2026-07-01 09:15:00'),
(2, 'Festival cultural reúne a miles de visitantes en el centro histórico', '<p>El tradicional festival cultural de la ciudad reunió este fin de semana a miles de visitantes que disfrutaron de música en vivo, gastronomía típica y exposiciones de artistas locales.</p><p>Las autoridades municipales informaron que la afluencia superó las expectativas y ya se planea una nueva edición ampliada para el próximo año.</p><p>Diversos artesanos también aprovecharon el evento para exhibir y comercializar sus productos.</p>', 2, 'Redacción Cultural', 5, 1, 1, '347143ea954d09facfb7f4426b7554d231046403b11209fef3e33cf0cc43f538', '2026-07-02 14:30:00', '2026-07-02 14:30:00'),
(3, 'Nueva ley de innovación tecnológica es aprobada por el congreso', '<p>El congreso aprobó una nueva ley orientada a impulsar la innovación tecnológica y facilitar la creación de empresas emergentes en el país.</p><p>La normativa contempla incentivos fiscales para startups y programas de capacitación en competencias digitales.</p><p>Especialistas del sector consideran que la medida podría atraer inversión extranjera en los próximos años.</p>', 1, NULL, 3, 1, 1, '0341ba26f9fa821aa21d869017f5284a72209aa65a517b9cf0a581980a54c4a8', '2026-07-03 08:00:00', '2026-07-03 08:00:00'),
(4, 'Congreso debate reforma electoral en sesión extraordinaria', '<p>En una sesión extraordinaria, el congreso inició el debate sobre una propuesta de reforma electoral que busca modernizar el sistema de votación.</p><p>Los legisladores discutieron distintos puntos de vista sobre la implementación de nuevas tecnologías en los procesos electorales.</p><p>Se espera que la discusión continúe durante las próximas semanas antes de someterse a votación final.</p>', 2, 'Redacción Política', 4, 1, 1, 'a4ffb4d14156ba42ae527926f7ef72de2c68511dae1242b7a8f49ead463cd111', '2026-07-04 11:45:00', '2026-07-04 11:45:00'),
(5, 'Ciudad se prepara para su tradicional feria anual de eventos', '<p>La ciudad ultima los preparativos para la tradicional feria anual de eventos, que este año contará con más de cien expositores y actividades para toda la familia.</p><p>Entre las novedades se incluyen zonas gastronómicas temáticas y un área infantil con juegos interactivos.</p><p>La feria se extenderá durante cinco días en el recinto ferial municipal.</p>', 1, NULL, 2, 1, 1, '863bc47eed8be5dad9b07b9f6716f2b28cfb023025f219d2c6968327e8360190', '2026-07-05 16:20:00', '2026-07-05 16:20:00'),
(6, 'Equipo local seguirá en la ciudad tras acuerdo por remodelación de estadio', '<p>Tras semanas de incertidumbre, el equipo de fútbol local llegó a un acuerdo con las autoridades municipales para permanecer en la ciudad, condicionado a la remodelación integral de su estadio.</p><p>El proyecto de remodelación iniciará el próximo año y contempla la ampliación de la capacidad y mejoras en accesibilidad.</p><p>La dirigencia del club expresó su satisfacción por el acuerdo alcanzado.</p>', 2, 'Redacción Deportiva', 1, 1, 1, 'ae1feb888aaf1a4ac53a2e9533538c6cf432eb907b2627319c5e08c280584080', '2026-07-06 07:00:00', '2026-07-06 07:00:00');

-- Noticias adicionales para cumplir el mínimo de 3 por categoría (RF: cada
-- categoría debe tener al menos 3 noticias). Firmas HMAC-SHA256 generadas
-- con la misma clave SIGNATURE_SECRET_KEY que las noticias 1-6.
INSERT INTO noticias (id, titulo, contenido, id_usuario, autor, id_categoria, publicado, activo, firma_digital, created_at, updated_at) VALUES
(7, 'Club juvenil gana torneo regional de baloncesto', '<p>El equipo juvenil de baloncesto de la ciudad se coronó campeón del torneo regional tras vencer en la final a su rival histórico por un ajustado marcador.</p><p>Los entrenadores destacaron el trabajo formativo de las categorías inferiores como base del logro.</p><p>El club anunció que el plantel será reconocido en un acto público la próxima semana.</p>', 2, 'Redacción Deportiva', 1, 1, 1, 'daa6ba9090c9538f8d48272369dee6147426babe29cae8eb1abbbeea30a65734', '2026-07-07 09:00:00', '2026-07-07 09:00:00'),
(8, 'Feria del libro abre sus puertas con récord de asistencia', '<p>La feria del libro de la ciudad inició su edición número quince con una afluencia de público que superó todas las expectativas de los organizadores.</p><p>Editoriales locales e internacionales presentan sus novedades durante los próximos diez días, con actividades para todas las edades.</p><p>Se espera que la feria cierre con más de cincuenta mil visitantes.</p>', 1, NULL, 2, 1, 1, 'e7f4502bd0bcc2b49cd166237cc091b2c0d0d79a94a64ea4fc2a9969d233b2e4', '2026-07-08 10:00:00', '2026-07-08 10:00:00'),
(9, 'Concierto benéfico recauda fondos para hospital infantil', '<p>Un concierto benéfico realizado en el parque central logró recaudar fondos destinados a la remodelación del ala pediátrica del hospital municipal.</p><p>Artistas locales participaron de forma gratuita para apoyar la causa.</p><p>Los organizadores agradecieron el respaldo masivo del público asistente.</p>', 2, 'Redacción Cultural', 2, 1, 1, '8e6d98920aecf317a5f2d51c40053ef97933b13bdfc605321d5f0b1f35b5fdf0', '2026-07-09 11:00:00', '2026-07-09 11:00:00'),
(10, 'Startup local lanza aplicación de movilidad urbana', '<p>Una startup fundada por jóvenes emprendedores presentó una aplicación móvil orientada a mejorar la movilidad urbana mediante rutas inteligentes de transporte.</p><p>El proyecto fue desarrollado con apoyo de un programa de incubación tecnológica de la ciudad.</p><p>Sus creadores esperan expandir el servicio a otras ciudades del país en los próximos meses.</p>', 1, NULL, 3, 1, 1, '97cd98af8d952f5160b47a017e820685a6bf7003b5e9004eef1e6113216ec98b', '2026-07-10 08:30:00', '2026-07-10 08:30:00'),
(11, 'Universidad presenta laboratorio de inteligencia artificial', '<p>Una universidad local inauguró un nuevo laboratorio dedicado a la investigación en inteligencia artificial y ciencia de datos.</p><p>El espacio contará con equipos de cómputo de alto rendimiento para proyectos estudiantiles y de investigación aplicada.</p><p>Autoridades académicas señalaron que se buscará establecer alianzas con empresas del sector tecnológico.</p>', 2, 'Redacción Tecnológica', 3, 1, 1, 'fd622e4242e20de5bcf1f11bc1e2e6830b1a5350607013bceff1447bd845e503', '2026-07-11 09:30:00', '2026-07-11 09:30:00'),
(12, 'Alcaldía anuncia plan de modernización vial', '<p>La alcaldía presentó un plan integral de modernización vial que contempla la rehabilitación de las principales avenidas de la ciudad.</p><p>El proyecto incluye la instalación de semáforos inteligentes y ciclovías en zonas de alto tránsito.</p><p>Las obras comenzarán de forma progresiva durante el próximo trimestre.</p>', 1, NULL, 4, 1, 1, '571a4d2d788c93a937e7d0eb918cd72b5e1bc11a0a97292c0bd06a71dd8bb484', '2026-07-12 10:30:00', '2026-07-12 10:30:00'),
(13, 'Comisión legislativa revisa presupuesto general del estado', '<p>La comisión de finanzas del congreso inició la revisión detallada del presupuesto general del estado para el próximo período fiscal.</p><p>Los legisladores analizan ajustes en distintas partidas antes de someter el documento a votación en el pleno.</p><p>Se espera que el debate se extienda durante varias semanas.</p>', 2, 'Redacción Política', 4, 1, 1, '6b85dc4ef02c376e338f0ba3ce9be4ebb6f09bf1ae8db81b2356d436d554db6e', '2026-07-13 11:30:00', '2026-07-13 11:30:00'),
(14, 'Museo nacional inaugura exposición de arte contemporáneo', '<p>El museo nacional abrió al público una nueva exposición dedicada a artistas contemporáneos de la región.</p><p>La muestra reúne pinturas, esculturas e instalaciones de más de veinte creadores locales.</p><p>La exposición permanecerá abierta durante los próximos tres meses con entrada gratuita.</p>', 1, NULL, 5, 1, 1, 'c91892cecf5a3e4af38b8c64de478f45ced549c6a530b3c02a71e40feecafe66', '2026-07-14 12:00:00', '2026-07-14 12:00:00'),
(15, 'Orquesta sinfónica ofrece concierto gratuito en la plaza central', '<p>La orquesta sinfónica nacional ofreció un concierto gratuito en la plaza central de la ciudad ante cientos de asistentes.</p><p>El repertorio incluyó piezas clásicas y composiciones de autores locales.</p><p>Las autoridades culturales anunciaron que este tipo de eventos se realizará de forma trimestral.</p>', 2, 'Redacción Cultural', 5, 1, 1, 'a6e42dd1845431fa189ac06d3765995e10e90d72d335e786d5471c008470e05d', '2026-07-15 13:00:00', '2026-07-15 13:00:00');

-- Imagenes reales (licencia libre, ver docs/CREDITOS_IMAGENES.md) para las 15 noticias:
-- 3 imagenes por noticia (portada + 2 adicionales).
INSERT INTO noticia_imagenes (id_noticia, ruta_imagen, ruta_thumbnail, orden, created_at) VALUES
(1, 'news/59a06add678f57cf2cdb5612b619183e.jpg', 'thumbnails/59a06add678f57cf2cdb5612b619183e.jpg', 0, NOW()),
(1, 'news/ff25428deb40a7cdd26105ba886eb129.jpg', 'thumbnails/ff25428deb40a7cdd26105ba886eb129.jpg', 1, NOW()),
(1, 'news/cca3e85e035b49f54a9aee09cd12454d.jpg', 'thumbnails/cca3e85e035b49f54a9aee09cd12454d.jpg', 2, NOW()),
(2, 'news/5380d2ca1865efbccec8b54cf98dfe1f.jpg', 'thumbnails/5380d2ca1865efbccec8b54cf98dfe1f.jpg', 0, NOW()),
(2, 'news/a07501e279b9f0496e3c8582cbb3c2d3.jpg', 'thumbnails/a07501e279b9f0496e3c8582cbb3c2d3.jpg', 1, NOW()),
(2, 'news/b8b2fd0fdad092f124619edf14ca5556.jpg', 'thumbnails/b8b2fd0fdad092f124619edf14ca5556.jpg', 2, NOW()),
(3, 'news/de9a8e749b61eaebd120b80df76af70b.jpg', 'thumbnails/de9a8e749b61eaebd120b80df76af70b.jpg', 0, NOW()),
(3, 'news/583d918eec0ead605d7b78bdd26f5119.jpg', 'thumbnails/583d918eec0ead605d7b78bdd26f5119.jpg', 1, NOW()),
(3, 'news/94bcdc92a0c65d7329242889c6c9745f.jpg', 'thumbnails/94bcdc92a0c65d7329242889c6c9745f.jpg', 2, NOW()),
(4, 'news/53e721583cb0b2c40182dd69186c9f3f.jpg', 'thumbnails/53e721583cb0b2c40182dd69186c9f3f.jpg', 0, NOW()),
(4, 'news/795242a12600138228141ff780e152b1.jpg', 'thumbnails/795242a12600138228141ff780e152b1.jpg', 1, NOW()),
(4, 'news/f98b2aaf0f2ad6a60ef79971140436fa.jpg', 'thumbnails/f98b2aaf0f2ad6a60ef79971140436fa.jpg', 2, NOW()),
(5, 'news/76359797ee12393511bc502aaf82053c.jpg', 'thumbnails/76359797ee12393511bc502aaf82053c.jpg', 0, NOW()),
(5, 'news/07b14fc7f80731a536c3c5fba870c889.jpg', 'thumbnails/07b14fc7f80731a536c3c5fba870c889.jpg', 1, NOW()),
(5, 'news/7652420eb79fa6966531659845a11b12.jpg', 'thumbnails/7652420eb79fa6966531659845a11b12.jpg', 2, NOW()),
(6, 'news/2e65d6ea467d9712963d9a150da7ff26.jpg', 'thumbnails/2e65d6ea467d9712963d9a150da7ff26.jpg', 0, NOW()),
(6, 'news/781aadb0571bfd2c63ded425e409fecc.jpg', 'thumbnails/781aadb0571bfd2c63ded425e409fecc.jpg', 1, NOW()),
(6, 'news/c0e89c8a7448577bd51b0bbcf1bf60e5.jpg', 'thumbnails/c0e89c8a7448577bd51b0bbcf1bf60e5.jpg', 2, NOW()),
(7, 'news/58a13e82ece1aab7002c1120ab73fb94.jpg', 'thumbnails/58a13e82ece1aab7002c1120ab73fb94.jpg', 0, NOW()),
(7, 'news/7bbee0eb15e2dad0ceb8eaceba58451a.jpg', 'thumbnails/7bbee0eb15e2dad0ceb8eaceba58451a.jpg', 1, NOW()),
(7, 'news/cae9155c3aa182d90845922c5477102a.jpg', 'thumbnails/cae9155c3aa182d90845922c5477102a.jpg', 2, NOW()),
(8, 'news/71792b3ee45443a9573ae64ca3736958.jpg', 'thumbnails/71792b3ee45443a9573ae64ca3736958.jpg', 0, NOW()),
(8, 'news/3dab81f62de3247f1524ed0a99e95bc2.jpg', 'thumbnails/3dab81f62de3247f1524ed0a99e95bc2.jpg', 1, NOW()),
(8, 'news/ac270b97f800467226850c2285921958.jpg', 'thumbnails/ac270b97f800467226850c2285921958.jpg', 2, NOW()),
(9, 'news/895b57ef501a6109ec27a3668e0f5622.jpg', 'thumbnails/895b57ef501a6109ec27a3668e0f5622.jpg', 0, NOW()),
(9, 'news/1577bb869b6df14c24cb4c73fca29353.jpg', 'thumbnails/1577bb869b6df14c24cb4c73fca29353.jpg', 1, NOW()),
(9, 'news/d02d4c4ebf19d881ece02ad81dafe0bc.jpg', 'thumbnails/d02d4c4ebf19d881ece02ad81dafe0bc.jpg', 2, NOW()),
(10, 'news/fc94b233288f1b1b305ef358be919481.jpg', 'thumbnails/fc94b233288f1b1b305ef358be919481.jpg', 0, NOW()),
(10, 'news/6619fd38c1afb19626bede996b6a8027.jpg', 'thumbnails/6619fd38c1afb19626bede996b6a8027.jpg', 1, NOW()),
(10, 'news/cfe3bce46bad3ac916267a8fa9497f73.jpg', 'thumbnails/cfe3bce46bad3ac916267a8fa9497f73.jpg', 2, NOW()),
(11, 'news/fb6f3651e8e995384aff8f7fbefec102.jpg', 'thumbnails/fb6f3651e8e995384aff8f7fbefec102.jpg', 0, NOW()),
(11, 'news/1cf57decb4aa33ab63d9ed463029ac4a.jpg', 'thumbnails/1cf57decb4aa33ab63d9ed463029ac4a.jpg', 1, NOW()),
(11, 'news/f551b9bfabf866d15bfb6c2fe84eca85.jpg', 'thumbnails/f551b9bfabf866d15bfb6c2fe84eca85.jpg', 2, NOW()),
(12, 'news/bd6ff62c678d2c3254d6043266e8cffc.jpg', 'thumbnails/bd6ff62c678d2c3254d6043266e8cffc.jpg', 0, NOW()),
(12, 'news/dc4e19f994513dcab6188b8e3a0e5d5a.jpg', 'thumbnails/dc4e19f994513dcab6188b8e3a0e5d5a.jpg', 1, NOW()),
(12, 'news/f40bb78259717ae8e22791fb91ba9e0e.jpg', 'thumbnails/f40bb78259717ae8e22791fb91ba9e0e.jpg', 2, NOW()),
(13, 'news/ac92612de79b7f72d1d05326278d000f.jpg', 'thumbnails/ac92612de79b7f72d1d05326278d000f.jpg', 0, NOW()),
(13, 'news/114abdaf00174463315122eb385c3606.jpg', 'thumbnails/114abdaf00174463315122eb385c3606.jpg', 1, NOW()),
(13, 'news/2ac8ec4e54ead5cafe50dd763e9aaaa0.jpg', 'thumbnails/2ac8ec4e54ead5cafe50dd763e9aaaa0.jpg', 2, NOW()),
(14, 'news/ce531be97bce5277a50dd166d4fa8c99.jpg', 'thumbnails/ce531be97bce5277a50dd166d4fa8c99.jpg', 0, NOW()),
(14, 'news/24b0948e06c36f81c024ce9db3803ff8.jpg', 'thumbnails/24b0948e06c36f81c024ce9db3803ff8.jpg', 1, NOW()),
(14, 'news/4e2e8cd1b432acc8ac7e0ae3c06ae3c7.jpg', 'thumbnails/4e2e8cd1b432acc8ac7e0ae3c06ae3c7.jpg', 2, NOW()),
(15, 'news/71e8e90479b25b4fc6d0b754be79f09d.jpg', 'thumbnails/71e8e90479b25b4fc6d0b754be79f09d.jpg', 0, NOW()),
(15, 'news/6fd32f00f6d8f75243be0e777515299c.jpg', 'thumbnails/6fd32f00f6d8f75243be0e777515299c.jpg', 1, NOW()),
(15, 'news/a3a749e7aa86732a9460bb9af8c40c8d.jpg', 'thumbnails/a3a749e7aa86732a9460bb9af8c40c8d.jpg', 2, NOW());

INSERT INTO comentarios (id_noticia, nombre_usuario, email, comentario, estado, respuesta, id_usuario_admin, created_at) VALUES
(1, 'Carlos Pérez', 'carlos.perez@example.com', 'Excelente noticia, felicitaciones al equipo por el esfuerzo mostrado.', 'aprobado', 'Gracias por su comentario, Carlos.', 1, '2026-07-01 10:00:00'),
(1, 'María Gómez', 'maria.gomez@example.com', 'Estaré pendiente de los próximos partidos, gran clasificación.', 'aprobado', NULL, NULL, '2026-07-01 11:30:00'),
(2, 'Luis Torres', 'luis.torres@example.com', 'El festival estuvo increíble este año, felicitaciones a los organizadores.', 'pendiente', NULL, NULL, '2026-07-02 15:00:00'),
(3, 'Ana Ramírez', 'ana.ramirez@example.com', 'Muy buena iniciativa para fomentar el emprendimiento tecnológico.', 'aprobado', NULL, NULL, '2026-07-03 09:00:00'),
(4, 'Usuario Anónimo', 'anonimo@example.com', 'Comentario ofensivo de prueba para demostrar el bloqueo.', 'bloqueado', NULL, 1, '2026-07-04 12:00:00');

INSERT INTO reacciones (id_noticia, tipo, ip_address, created_at) VALUES
(1, 'like', '190.10.20.1', '2026-07-01 10:05:00'),
(1, 'like', '190.10.20.2', '2026-07-01 12:00:00'),
(1, 'like', '190.10.20.3', '2026-07-01 18:00:00'),
(2, 'like', '190.10.20.4', '2026-07-02 15:10:00'),
(3, 'like', '190.10.20.5', '2026-07-03 10:00:00'),
(3, 'like', '190.10.20.6', '2026-07-03 11:00:00');

INSERT INTO login_logs (usuario, ip_address, exito, user_agent, fecha_hora) VALUES
('admin@hotmail.com', '127.0.0.1', 1, 'Mozilla/5.0 (Demo Seed)', '2026-07-05 08:00:00'),
('admin@hotmail.com', '127.0.0.1', 0, 'Mozilla/5.0 (Demo Seed)', '2026-07-05 20:15:00'),
('editor@hotmail.com', '127.0.0.1', 1, 'Mozilla/5.0 (Demo Seed)', '2026-07-06 08:30:00');

INSERT INTO visitas (session_id, ip_address, created_at) VALUES
('seed_session_1', '190.10.20.1', '2026-07-01 10:00:00'),
('seed_session_2', '190.10.20.2', '2026-07-02 11:00:00'),
('seed_session_3', '190.10.20.3', '2026-07-03 12:00:00'),
('seed_session_4', '190.10.20.4', '2026-07-04 13:00:00'),
('seed_session_5', '190.10.20.5', '2026-07-05 14:00:00');
