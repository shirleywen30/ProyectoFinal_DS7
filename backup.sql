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
-- Usuarios administrativos del sistema (rol admin / editor).
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre            VARCHAR(100)        NOT NULL,
    email             VARCHAR(150)        NOT NULL,
    password          VARCHAR(255)        NOT NULL COMMENT 'Hash bcrypt (password_hash)',
    rol               ENUM('admin','editor') NOT NULL DEFAULT 'editor',
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
-- -----------------------------------------------------------------------------
CREATE TABLE noticias (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo         VARCHAR(200) NOT NULL,
    contenido      TEXT         NOT NULL,
    id_usuario     INT UNSIGNED NOT NULL COMMENT 'Usuario que creó la noticia',
    autor          VARCHAR(120) NULL COMMENT 'Autor visible (opcional, distinto del usuario del sistema)',
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
    nombre_usuario    VARCHAR(100) NOT NULL,
    email             VARCHAR(150) NOT NULL,
    comentario        TEXT         NOT NULL,
    estado            ENUM('pendiente','aprobado','bloqueado') NOT NULL DEFAULT 'pendiente',
    respuesta         TEXT         NULL COMMENT 'Respuesta del administrador',
    id_usuario_admin  INT UNSIGNED NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comentarios_noticia FOREIGN KEY (id_noticia) REFERENCES noticias(id) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_admin FOREIGN KEY (id_usuario_admin) REFERENCES usuarios(id) ON DELETE SET NULL,
    KEY idx_comentarios_estado (estado),
    KEY idx_comentarios_noticia (id_noticia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: reacciones ("me gusta"), únicas por IP + noticia.
-- -----------------------------------------------------------------------------
CREATE TABLE reacciones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_noticia  INT UNSIGNED NOT NULL,
    tipo        VARCHAR(20)  NOT NULL DEFAULT 'like',
    ip_address  VARCHAR(45)  NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reacciones_noticia FOREIGN KEY (id_noticia) REFERENCES noticias(id) ON DELETE CASCADE,
    UNIQUE KEY uq_reaccion_ip_noticia (id_noticia, ip_address)
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

-- Usuarios: admin / root2514  |  editor / Editor2024
-- Las contraseñas están almacenadas con password_hash() (bcrypt, cost 12).
INSERT INTO usuarios (id, nombre, email, password, rol, activo, intentos_fallidos, created_at) VALUES
(1, 'admin', 'admin@sistemanoticias.local', '$2y$12$Ymhnqwo3YnQlCD3XQy1AT.mf9EPlDb0ca1NuYGkvqTv/rew2k7G9i', 'admin', 1, 0, NOW()),
(2, 'Editor Demo', 'editor@sistemanoticias.local', '$2y$12$BgKw9yBVc3XqkOVIlD5Fhu846YYWA6T5hs/mCWgk6mLTsfhHPa.De', 'editor', 1, 0, NOW());

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
(6, 'Equipo local seguirá en la ciudad tras acuerdo por remodelación de estadio', '<p>Tras semanas de incertidumbre, el equipo de fútbol local llegó a un acuerdo con las autoridades municipales para permanecer en la ciudad, condicionado a la remodelación integral de su estadio.</p><p>El proyecto de remodelación iniciará el próximo año y contempla la ampliación de la capacidad y mejoras en accesibilidad.</p><p>La dirigencia del club expresó su satisfacción por el acuerdo alcanzado.</p>', 2, 'Redacción Deportiva', 1, 0, 1, 'ae1feb888aaf1a4ac53a2e9533538c6cf432eb907b2627319c5e08c280584080', '2026-07-06 07:00:00', '2026-07-06 07:00:00');

-- Imágenes de demostración (3 por noticia: portada + 2 adicionales).
-- Los archivos físicos correspondientes ya se incluyen en /public/uploads/.
INSERT INTO noticia_imagenes (id_noticia, ruta_imagen, ruta_thumbnail, orden, created_at) VALUES
(1, 'news/seed_news1_img1.jpg', 'thumbnails/seed_news1_img1.jpg', 0, NOW()),
(1, 'news/seed_news1_img2.jpg', 'thumbnails/seed_news1_img2.jpg', 1, NOW()),
(1, 'news/seed_news1_img3.jpg', 'thumbnails/seed_news1_img3.jpg', 2, NOW()),
(2, 'news/seed_news2_img1.jpg', 'thumbnails/seed_news2_img1.jpg', 0, NOW()),
(2, 'news/seed_news2_img2.jpg', 'thumbnails/seed_news2_img2.jpg', 1, NOW()),
(2, 'news/seed_news2_img3.jpg', 'thumbnails/seed_news2_img3.jpg', 2, NOW()),
(3, 'news/seed_news3_img1.jpg', 'thumbnails/seed_news3_img1.jpg', 0, NOW()),
(3, 'news/seed_news3_img2.jpg', 'thumbnails/seed_news3_img2.jpg', 1, NOW()),
(3, 'news/seed_news3_img3.jpg', 'thumbnails/seed_news3_img3.jpg', 2, NOW()),
(4, 'news/seed_news4_img1.jpg', 'thumbnails/seed_news4_img1.jpg', 0, NOW()),
(4, 'news/seed_news4_img2.jpg', 'thumbnails/seed_news4_img2.jpg', 1, NOW()),
(4, 'news/seed_news4_img3.jpg', 'thumbnails/seed_news4_img3.jpg', 2, NOW()),
(5, 'news/seed_news5_img1.jpg', 'thumbnails/seed_news5_img1.jpg', 0, NOW()),
(5, 'news/seed_news5_img2.jpg', 'thumbnails/seed_news5_img2.jpg', 1, NOW()),
(5, 'news/seed_news5_img3.jpg', 'thumbnails/seed_news5_img3.jpg', 2, NOW()),
(6, 'news/seed_news6_img1.jpg', 'thumbnails/seed_news6_img1.jpg', 0, NOW()),
(6, 'news/seed_news6_img2.jpg', 'thumbnails/seed_news6_img2.jpg', 1, NOW()),
(6, 'news/seed_news6_img3.jpg', 'thumbnails/seed_news6_img3.jpg', 2, NOW());

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
('admin', '127.0.0.1', 1, 'Mozilla/5.0 (Demo Seed)', '2026-07-05 08:00:00'),
('admin', '127.0.0.1', 0, 'Mozilla/5.0 (Demo Seed)', '2026-07-05 20:15:00'),
('editor@sistemanoticias.local', '127.0.0.1', 1, 'Mozilla/5.0 (Demo Seed)', '2026-07-06 08:30:00');

INSERT INTO visitas (session_id, ip_address, created_at) VALUES
('seed_session_1', '190.10.20.1', '2026-07-01 10:00:00'),
('seed_session_2', '190.10.20.2', '2026-07-02 11:00:00'),
('seed_session_3', '190.10.20.3', '2026-07-03 12:00:00'),
('seed_session_4', '190.10.20.4', '2026-07-04 13:00:00'),
('seed_session_5', '190.10.20.5', '2026-07-05 14:00:00');
