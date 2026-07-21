-- =============================================================================
-- Este dump crea la base de datos si no existe y la selecciona automáticamente,
-- para que cualquier persona pueda importarlo sin pasos previos.
-- =============================================================================
CREATE DATABASE IF NOT EXISTS `sistema_noticias` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sistema_noticias`;

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 21-07-2026 a las 15:17:23
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.5.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_noticias`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
(1, 'Deporte', 'Noticias relacionadas con deportes locales e internacionales.', 1, '2026-07-20 16:22:50'),
(2, 'Eventos', 'Eventos culturales, sociales y comunitarios.', 1, '2026-07-20 16:22:50'),
(3, 'Tecnología', 'Avances, lanzamientos y tendencias tecnológicas.', 1, '2026-07-20 16:22:50'),
(4, 'Política', 'Noticias sobre gobierno, congreso y política nacional.', 1, '2026-07-20 16:22:50'),
(5, 'Cultura', 'Arte, música, tradiciones y patrimonio cultural.', 1, '2026-07-20 16:22:50'),
(6, 'Cine', 'Cinema', 1, '2026-07-21 14:09:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

DROP TABLE IF EXISTS `comentarios`;
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_noticia` int UNSIGNED NOT NULL,
  `id_usuario` int UNSIGNED DEFAULT NULL,
  `nombre_usuario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','aprobado','bloqueado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `respuesta` text COLLATE utf8mb4_unicode_ci COMMENT 'Respuesta del administrador',
  `id_usuario_admin` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comentarios_admin` (`id_usuario_admin`),
  KEY `idx_comentarios_estado` (`estado`),
  KEY `idx_comentarios_noticia` (`id_noticia`),
  KEY `fk_comentarios_usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`id`, `id_noticia`, `id_usuario`, `nombre_usuario`, `email`, `comentario`, `estado`, `respuesta`, `id_usuario_admin`, `created_at`) VALUES
(1, 1, NULL, 'Carlos Pérez', 'carlos.perez@example.com', 'Excelente noticia, felicitaciones al equipo por el esfuerzo mostrado.', 'aprobado', 'Gracias por su comentario, Carlos.', 1, '2026-07-01 10:00:00'),
(2, 1, NULL, 'María Gómez', 'maria.gomez@example.com', 'Estaré pendiente de los próximos partidos, gran clasificación.', 'aprobado', NULL, NULL, '2026-07-01 11:30:00'),
(3, 2, NULL, 'Luis Torres', 'luis.torres@example.com', 'El festival estuvo increíble este año, felicitaciones a los organizadores.', 'pendiente', NULL, NULL, '2026-07-02 15:00:00'),
(4, 3, NULL, 'Ana Ramírez', 'ana.ramirez@example.com', 'Muy buena iniciativa para fomentar el emprendimiento tecnológico.', 'aprobado', NULL, NULL, '2026-07-03 09:00:00'),
(5, 4, NULL, 'Usuario Anónimo', 'anonimo@example.com', 'Comentario ofensivo de prueba para demostrar el bloqueo.', 'bloqueado', NULL, 1, '2026-07-04 12:00:00'),
(6, 1, NULL, 'Juan', 'Juanj11@gmail.com', 'Espero con ansias verlos debutar!', 'aprobado', NULL, NULL, '2026-07-21 05:49:21'),
(11, 14, NULL, 'Jose', 'Jose@utp.com', 'Me mola mucho esta Noticia :D.', 'aprobado', NULL, NULL, '2026-07-21 06:14:16'),
(12, 1, NULL, 'Shirley', 'Shirley11@gmail.com', 'Increible', 'aprobado', NULL, NULL, '2026-07-21 13:14:20'),
(14, 17, NULL, 'Jose', 'barahona@utp.ac.pa', 'Si que mola la pelicula!!!', 'aprobado', NULL, NULL, '2026-07-21 14:21:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Usuario o correo ingresado',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exito` tinyint(1) NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_logs_usuario` (`usuario`),
  KEY `idx_login_logs_fecha` (`fecha_hora`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `login_logs`
--

INSERT INTO `login_logs` (`id`, `usuario`, `ip_address`, `exito`, `user_agent`, `fecha_hora`) VALUES
(1, 'admin@hotmail.com', '127.0.0.1', 1, 'Mozilla/5.0 (Demo Seed)', '2026-07-05 08:00:00'),
(2, 'admin@hotmail.com', '127.0.0.1', 0, 'Mozilla/5.0 (Demo Seed)', '2026-07-05 20:15:00'),
(3, 'editor@hotmail.com', '127.0.0.1', 1, 'Mozilla/5.0 (Demo Seed)', '2026-07-06 08:30:00'),
(4, 'supervisor Demo', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-20 21:43:08'),
(5, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-20 21:46:44'),
(6, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-20 22:04:20'),
(7, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 06:14:37'),
(8, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 12:14:13'),
(9, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 13:08:07'),
(10, 'luifer11@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:02:11'),
(11, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:03:22'),
(12, 'luifer11@gmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:04:01'),
(13, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:05:17'),
(14, 'admin', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:17:57'),
(15, 'supervisor Demo', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:24:04'),
(16, 'editor', '::1', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:24:36'),
(17, 'editor@hotmail.com', '::1', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 14:24:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

DROP TABLE IF EXISTS `noticias`;
CREATE TABLE IF NOT EXISTS `noticias` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` int UNSIGNED NOT NULL COMMENT 'Usuario que creó la noticia',
  `autor` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Autor visible (opcional, distinto del usuario del sistema)',
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Enlace embebible de YouTube/Vimeo (opcional)',
  `id_categoria` int UNSIGNED NOT NULL,
  `publicado` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Baja lógica',
  `firma_digital` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'HMAC-SHA256 de integridad',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_noticias_usuario` (`id_usuario`),
  KEY `idx_noticias_publicado_activo` (`publicado`,`activo`),
  KEY `idx_noticias_categoria` (`id_categoria`),
  KEY `idx_noticias_fecha` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `noticias`
--

INSERT INTO `noticias` (`id`, `titulo`, `contenido`, `id_usuario`, `autor`, `video_url`, `id_categoria`, `publicado`, `activo`, `firma_digital`, `created_at`, `updated_at`) VALUES
(1, 'Selección nacional clasifica al mundial tras vibrante victoria', '<p>La selección nacional de fútbol logró anoche su clasificación al mundial luego de una vibrante victoria por 2 a 1 disputada ante una multitud que colmó el estadio principal.</p><p>El técnico destacó el esfuerzo colectivo del equipo y aseguró que el objetivo ahora es preparar de la mejor manera posible la fase final del torneo.</p><p>Miles de aficionados celebraron en las calles la clasificación, que no se lograba desde hace más de una década.</p>', 1, NULL, NULL, 1, 1, 1, '73deb67e3d09548cd5785ab4b2a2b4ea478e4a366d5fdbbf398de5978c7de66a', '2026-07-01 09:15:00', '2026-07-01 09:15:00'),
(2, 'Festival cultural reúne a miles de visitantes en el centro histórico', '<p>El tradicional festival cultural de la ciudad reunió este fin de semana a miles de visitantes que disfrutaron de música en vivo, gastronomía típica y exposiciones de artistas locales.</p><p>Las autoridades municipales informaron que la afluencia superó las expectativas y ya se planea una nueva edición ampliada para el próximo año.</p><p>Diversos artesanos también aprovecharon el evento para exhibir y comercializar sus productos.</p>', 2, 'Redacción Cultural', NULL, 5, 1, 1, '347143ea954d09facfb7f4426b7554d231046403b11209fef3e33cf0cc43f538', '2026-07-02 14:30:00', '2026-07-02 14:30:00'),
(3, 'Nueva ley de innovación tecnológica es aprobada por el congreso', '<p>El congreso aprobó una nueva ley orientada a impulsar la innovación tecnológica y facilitar la creación de empresas emergentes en el país.</p><p>La normativa contempla incentivos fiscales para startups y programas de capacitación en competencias digitales.</p><p>Especialistas del sector consideran que la medida podría atraer inversión extranjera en los próximos años.</p>', 1, NULL, NULL, 3, 1, 1, '0341ba26f9fa821aa21d869017f5284a72209aa65a517b9cf0a581980a54c4a8', '2026-07-03 08:00:00', '2026-07-03 08:00:00'),
(4, 'Congreso debate reforma electoral en sesión extraordinaria', '<p>En una sesión extraordinaria, el congreso inició el debate sobre una propuesta de reforma electoral que busca modernizar el sistema de votación.</p><p>Los legisladores discutieron distintos puntos de vista sobre la implementación de nuevas tecnologías en los procesos electorales.</p><p>Se espera que la discusión continúe durante las próximas semanas antes de someterse a votación final.</p>', 2, 'Redacción Política', NULL, 4, 1, 1, 'a4ffb4d14156ba42ae527926f7ef72de2c68511dae1242b7a8f49ead463cd111', '2026-07-04 11:45:00', '2026-07-04 11:45:00'),
(5, 'Ciudad se prepara para su tradicional feria anual de eventos', '<p>La ciudad ultima los preparativos para la tradicional feria anual de eventos, que este año contará con más de cien expositores y actividades para toda la familia.</p><p>Entre las novedades se incluyen zonas gastronómicas temáticas y un área infantil con juegos interactivos.</p><p>La feria se extenderá durante cinco días en el recinto ferial municipal.</p>', 1, NULL, NULL, 2, 1, 1, '863bc47eed8be5dad9b07b9f6716f2b28cfb023025f219d2c6968327e8360190', '2026-07-05 16:20:00', '2026-07-05 16:20:00'),
(6, 'Equipo local seguirá en la ciudad tras acuerdo por remodelación de estadio', '<p>Tras semanas de incertidumbre, el equipo de fútbol local llegó a un acuerdo con las autoridades municipales para permanecer en la ciudad, condicionado a la remodelación integral de su estadio.</p><p>El proyecto de remodelación iniciará el próximo año y contempla la ampliación de la capacidad y mejoras en accesibilidad.</p><p>La dirigencia del club expresó su satisfacción por el acuerdo alcanzado.</p>', 2, 'Redacción Deportiva', NULL, 1, 1, 1, 'ae1feb888aaf1a4ac53a2e9533538c6cf432eb907b2627319c5e08c280584080', '2026-07-06 07:00:00', '2026-07-06 07:00:00'),
(7, 'Club juvenil gana torneo regional de baloncesto', '<p>El equipo juvenil de baloncesto de la ciudad se coronó campeón del torneo regional tras vencer en la final a su rival histórico por un ajustado marcador.</p><p>Los entrenadores destacaron el trabajo formativo de las categorías inferiores como base del logro.</p><p>El club anunció que el plantel será reconocido en un acto público la próxima semana.</p>', 2, 'Redacción Deportiva', NULL, 1, 1, 1, 'daa6ba9090c9538f8d48272369dee6147426babe29cae8eb1abbbeea30a65734', '2026-07-07 09:00:00', '2026-07-07 09:00:00'),
(8, 'Feria del libro abre sus puertas con récord de asistencia', '<p>La feria del libro de la ciudad inició su edición número quince con una afluencia de público que superó todas las expectativas de los organizadores.</p><p>Editoriales locales e internacionales presentan sus novedades durante los próximos diez días, con actividades para todas las edades.</p><p>Se espera que la feria cierre con más de cincuenta mil visitantes.</p>', 1, NULL, NULL, 2, 1, 1, 'e7f4502bd0bcc2b49cd166237cc091b2c0d0d79a94a64ea4fc2a9969d233b2e4', '2026-07-08 10:00:00', '2026-07-08 10:00:00'),
(9, 'Concierto benéfico recauda fondos para hospital infantil', '<p>Un concierto benéfico realizado en el parque central logró recaudar fondos destinados a la remodelación del ala pediátrica del hospital municipal.</p><p>Artistas locales participaron de forma gratuita para apoyar la causa.</p><p>Los organizadores agradecieron el respaldo masivo del público asistente.</p>', 2, 'Redacción Cultural', NULL, 2, 1, 1, '8e6d98920aecf317a5f2d51c40053ef97933b13bdfc605321d5f0b1f35b5fdf0', '2026-07-09 11:00:00', '2026-07-09 11:00:00'),
(10, 'Startup local lanza aplicación de movilidad urbana', '<p>Una startup fundada por jóvenes emprendedores presentó una aplicación móvil orientada a mejorar la movilidad urbana mediante rutas inteligentes de transporte.</p><p>El proyecto fue desarrollado con apoyo de un programa de incubación tecnológica de la ciudad.</p><p>Sus creadores esperan expandir el servicio a otras ciudades del país en los próximos meses.</p>', 1, NULL, NULL, 3, 1, 1, '97cd98af8d952f5160b47a017e820685a6bf7003b5e9004eef1e6113216ec98b', '2026-07-10 08:30:00', '2026-07-10 08:30:00'),
(11, 'Universidad presenta laboratorio de inteligencia artificial', '<p>Una universidad local inauguró un nuevo laboratorio dedicado a la investigación en inteligencia artificial y ciencia de datos.</p><p>El espacio contará con equipos de cómputo de alto rendimiento para proyectos estudiantiles y de investigación aplicada.</p><p>Autoridades académicas señalaron que se buscará establecer alianzas con empresas del sector tecnológico.</p>', 2, 'Redacción Tecnológica', NULL, 3, 1, 1, 'fd622e4242e20de5bcf1f11bc1e2e6830b1a5350607013bceff1447bd845e503', '2026-07-11 09:30:00', '2026-07-11 09:30:00'),
(12, 'Alcaldía anuncia plan de modernización vial', '<p>La alcaldía presentó un plan integral de modernización vial que contempla la rehabilitación de las principales avenidas de la ciudad.</p><p>El proyecto incluye la instalación de semáforos inteligentes y ciclovías en zonas de alto tránsito.</p><p>Las obras comenzarán de forma progresiva durante el próximo trimestre.</p>', 1, NULL, NULL, 4, 1, 1, '571a4d2d788c93a937e7d0eb918cd72b5e1bc11a0a97292c0bd06a71dd8bb484', '2026-07-12 10:30:00', '2026-07-12 10:30:00'),
(13, 'Comisión legislativa revisa presupuesto general del estado', '<p>La comisión de finanzas del congreso inició la revisión detallada del presupuesto general del estado para el próximo período fiscal.</p><p>Los legisladores analizan ajustes en distintas partidas antes de someter el documento a votación en el pleno.</p><p>Se espera que el debate se extienda durante varias semanas.</p>', 2, 'Redacción Política', NULL, 4, 1, 1, '6b85dc4ef02c376e338f0ba3ce9be4ebb6f09bf1ae8db81b2356d436d554db6e', '2026-07-13 11:30:00', '2026-07-21 14:25:26'),
(14, 'Museo nacional inaugura exposición de arte contemporáneo', '<p>El museo nacional abrió al público una nueva exposición dedicada a artistas contemporáneos de la región.</p><p>La muestra reúne pinturas, esculturas e instalaciones de más de veinte creadores locales.</p><p>La exposición permanecerá abierta durante los próximos tres meses con entrada gratuita.</p>', 1, NULL, NULL, 5, 1, 1, 'c91892cecf5a3e4af38b8c64de478f45ced549c6a530b3c02a71e40feecafe66', '2026-07-14 12:00:00', '2026-07-14 12:00:00'),
(15, 'Orquesta sinfónica ofrece concierto gratuito en la plaza central', '<p>La orquesta sinfónica nacional ofreció un concierto gratuito en la plaza central de la ciudad ante cientos de asistentes.</p><p>El repertorio incluyó piezas clásicas y composiciones de autores locales.</p><p>Las autoridades culturales anunciaron que este tipo de eventos se realizará de forma trimestral.</p>', 2, 'Redacción Cultural', NULL, 5, 1, 1, 'a6e42dd1845431fa189ac06d3765995e10e90d72d335e786d5471c008470e05d', '2026-07-15 13:00:00', '2026-07-15 13:00:00'),
(17, 'Spiderman sale pronto', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.', 1, 'Luifer', NULL, 6, 1, 1, '99d74b56fa96503af7b1d0988585704848b8c31c03ca7ff0c315f2f0c557c6ac', '2026-07-21 14:20:50', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticia_imagenes`
--

DROP TABLE IF EXISTS `noticia_imagenes`;
CREATE TABLE IF NOT EXISTS `noticia_imagenes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_noticia` int UNSIGNED NOT NULL,
  `ruta_imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ruta relativa dentro de /public/uploads/',
  `ruta_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_imagenes_noticia` (`id_noticia`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `noticia_imagenes`
--

INSERT INTO `noticia_imagenes` (`id`, `id_noticia`, `ruta_imagen`, `ruta_thumbnail`, `orden`, `created_at`) VALUES
(1, 1, 'news/59a06add678f57cf2cdb5612b619183e.jpg', 'thumbnails/59a06add678f57cf2cdb5612b619183e.jpg', 0, '2026-07-20 16:22:50'),
(2, 1, 'news/ff25428deb40a7cdd26105ba886eb129.jpg', 'thumbnails/ff25428deb40a7cdd26105ba886eb129.jpg', 1, '2026-07-20 16:22:50'),
(3, 1, 'news/cca3e85e035b49f54a9aee09cd12454d.jpg', 'thumbnails/cca3e85e035b49f54a9aee09cd12454d.jpg', 2, '2026-07-20 16:22:50'),
(4, 2, 'news/5380d2ca1865efbccec8b54cf98dfe1f.jpg', 'thumbnails/5380d2ca1865efbccec8b54cf98dfe1f.jpg', 0, '2026-07-20 16:22:50'),
(5, 2, 'news/a07501e279b9f0496e3c8582cbb3c2d3.jpg', 'thumbnails/a07501e279b9f0496e3c8582cbb3c2d3.jpg', 1, '2026-07-20 16:22:50'),
(6, 2, 'news/b8b2fd0fdad092f124619edf14ca5556.jpg', 'thumbnails/b8b2fd0fdad092f124619edf14ca5556.jpg', 2, '2026-07-20 16:22:50'),
(7, 3, 'news/de9a8e749b61eaebd120b80df76af70b.jpg', 'thumbnails/de9a8e749b61eaebd120b80df76af70b.jpg', 0, '2026-07-20 16:22:50'),
(8, 3, 'news/583d918eec0ead605d7b78bdd26f5119.jpg', 'thumbnails/583d918eec0ead605d7b78bdd26f5119.jpg', 1, '2026-07-20 16:22:50'),
(9, 3, 'news/94bcdc92a0c65d7329242889c6c9745f.jpg', 'thumbnails/94bcdc92a0c65d7329242889c6c9745f.jpg', 2, '2026-07-20 16:22:50'),
(10, 4, 'news/53e721583cb0b2c40182dd69186c9f3f.jpg', 'thumbnails/53e721583cb0b2c40182dd69186c9f3f.jpg', 0, '2026-07-20 16:22:50'),
(11, 4, 'news/795242a12600138228141ff780e152b1.jpg', 'thumbnails/795242a12600138228141ff780e152b1.jpg', 1, '2026-07-20 16:22:50'),
(12, 4, 'news/f98b2aaf0f2ad6a60ef79971140436fa.jpg', 'thumbnails/f98b2aaf0f2ad6a60ef79971140436fa.jpg', 2, '2026-07-20 16:22:50'),
(13, 5, 'news/76359797ee12393511bc502aaf82053c.jpg', 'thumbnails/76359797ee12393511bc502aaf82053c.jpg', 0, '2026-07-20 16:22:50'),
(14, 5, 'news/07b14fc7f80731a536c3c5fba870c889.jpg', 'thumbnails/07b14fc7f80731a536c3c5fba870c889.jpg', 1, '2026-07-20 16:22:50'),
(15, 5, 'news/7652420eb79fa6966531659845a11b12.jpg', 'thumbnails/7652420eb79fa6966531659845a11b12.jpg', 2, '2026-07-20 16:22:50'),
(16, 6, 'news/2e65d6ea467d9712963d9a150da7ff26.jpg', 'thumbnails/2e65d6ea467d9712963d9a150da7ff26.jpg', 0, '2026-07-20 16:22:50'),
(17, 6, 'news/781aadb0571bfd2c63ded425e409fecc.jpg', 'thumbnails/781aadb0571bfd2c63ded425e409fecc.jpg', 1, '2026-07-20 16:22:50'),
(18, 6, 'news/c0e89c8a7448577bd51b0bbcf1bf60e5.jpg', 'thumbnails/c0e89c8a7448577bd51b0bbcf1bf60e5.jpg', 2, '2026-07-20 16:22:50'),
(19, 7, 'news/58a13e82ece1aab7002c1120ab73fb94.jpg', 'thumbnails/58a13e82ece1aab7002c1120ab73fb94.jpg', 0, '2026-07-20 16:22:50'),
(20, 7, 'news/7bbee0eb15e2dad0ceb8eaceba58451a.jpg', 'thumbnails/7bbee0eb15e2dad0ceb8eaceba58451a.jpg', 1, '2026-07-20 16:22:50'),
(21, 7, 'news/cae9155c3aa182d90845922c5477102a.jpg', 'thumbnails/cae9155c3aa182d90845922c5477102a.jpg', 2, '2026-07-20 16:22:50'),
(22, 8, 'news/71792b3ee45443a9573ae64ca3736958.jpg', 'thumbnails/71792b3ee45443a9573ae64ca3736958.jpg', 0, '2026-07-20 16:22:50'),
(23, 8, 'news/3dab81f62de3247f1524ed0a99e95bc2.jpg', 'thumbnails/3dab81f62de3247f1524ed0a99e95bc2.jpg', 1, '2026-07-20 16:22:50'),
(24, 8, 'news/ac270b97f800467226850c2285921958.jpg', 'thumbnails/ac270b97f800467226850c2285921958.jpg', 2, '2026-07-20 16:22:50'),
(25, 9, 'news/895b57ef501a6109ec27a3668e0f5622.jpg', 'thumbnails/895b57ef501a6109ec27a3668e0f5622.jpg', 0, '2026-07-20 16:22:50'),
(26, 9, 'news/1577bb869b6df14c24cb4c73fca29353.jpg', 'thumbnails/1577bb869b6df14c24cb4c73fca29353.jpg', 1, '2026-07-20 16:22:50'),
(27, 9, 'news/d02d4c4ebf19d881ece02ad81dafe0bc.jpg', 'thumbnails/d02d4c4ebf19d881ece02ad81dafe0bc.jpg', 2, '2026-07-20 16:22:50'),
(28, 10, 'news/fc94b233288f1b1b305ef358be919481.jpg', 'thumbnails/fc94b233288f1b1b305ef358be919481.jpg', 0, '2026-07-20 16:22:50'),
(29, 10, 'news/6619fd38c1afb19626bede996b6a8027.jpg', 'thumbnails/6619fd38c1afb19626bede996b6a8027.jpg', 1, '2026-07-20 16:22:50'),
(30, 10, 'news/cfe3bce46bad3ac916267a8fa9497f73.jpg', 'thumbnails/cfe3bce46bad3ac916267a8fa9497f73.jpg', 2, '2026-07-20 16:22:50'),
(31, 11, 'news/fb6f3651e8e995384aff8f7fbefec102.jpg', 'thumbnails/fb6f3651e8e995384aff8f7fbefec102.jpg', 0, '2026-07-20 16:22:50'),
(32, 11, 'news/1cf57decb4aa33ab63d9ed463029ac4a.jpg', 'thumbnails/1cf57decb4aa33ab63d9ed463029ac4a.jpg', 1, '2026-07-20 16:22:50'),
(33, 11, 'news/f551b9bfabf866d15bfb6c2fe84eca85.jpg', 'thumbnails/f551b9bfabf866d15bfb6c2fe84eca85.jpg', 2, '2026-07-20 16:22:50'),
(34, 12, 'news/bd6ff62c678d2c3254d6043266e8cffc.jpg', 'thumbnails/bd6ff62c678d2c3254d6043266e8cffc.jpg', 0, '2026-07-20 16:22:50'),
(35, 12, 'news/dc4e19f994513dcab6188b8e3a0e5d5a.jpg', 'thumbnails/dc4e19f994513dcab6188b8e3a0e5d5a.jpg', 1, '2026-07-20 16:22:50'),
(36, 12, 'news/f40bb78259717ae8e22791fb91ba9e0e.jpg', 'thumbnails/f40bb78259717ae8e22791fb91ba9e0e.jpg', 2, '2026-07-20 16:22:50'),
(37, 13, 'news/ac92612de79b7f72d1d05326278d000f.jpg', 'thumbnails/ac92612de79b7f72d1d05326278d000f.jpg', 1, '2026-07-20 16:22:50'),
(38, 13, 'news/114abdaf00174463315122eb385c3606.jpg', 'thumbnails/114abdaf00174463315122eb385c3606.jpg', 2, '2026-07-20 16:22:50'),
(39, 13, 'news/2ac8ec4e54ead5cafe50dd763e9aaaa0.jpg', 'thumbnails/2ac8ec4e54ead5cafe50dd763e9aaaa0.jpg', 0, '2026-07-20 16:22:50'),
(40, 14, 'news/ce531be97bce5277a50dd166d4fa8c99.jpg', 'thumbnails/ce531be97bce5277a50dd166d4fa8c99.jpg', 0, '2026-07-20 16:22:50'),
(41, 14, 'news/24b0948e06c36f81c024ce9db3803ff8.jpg', 'thumbnails/24b0948e06c36f81c024ce9db3803ff8.jpg', 1, '2026-07-20 16:22:50'),
(42, 14, 'news/4e2e8cd1b432acc8ac7e0ae3c06ae3c7.jpg', 'thumbnails/4e2e8cd1b432acc8ac7e0ae3c06ae3c7.jpg', 2, '2026-07-20 16:22:50'),
(43, 15, 'news/71e8e90479b25b4fc6d0b754be79f09d.jpg', 'thumbnails/71e8e90479b25b4fc6d0b754be79f09d.jpg', 0, '2026-07-20 16:22:50'),
(44, 15, 'news/6fd32f00f6d8f75243be0e777515299c.jpg', 'thumbnails/6fd32f00f6d8f75243be0e777515299c.jpg', 1, '2026-07-20 16:22:50'),
(45, 15, 'news/a3a749e7aa86732a9460bb9af8c40c8d.jpg', 'thumbnails/a3a749e7aa86732a9460bb9af8c40c8d.jpg', 2, '2026-07-20 16:22:50'),
(49, 17, 'news/b5c2b5fa0f2f2f2cf3ce0dd7ef0a6b80.jpg', 'thumbnails/b5c2b5fa0f2f2f2cf3ce0dd7ef0a6b80.jpg', 0, '2026-07-21 14:20:50'),
(50, 17, 'news/a51a8695ea38056db868edc178b4c1ab.jpg', 'thumbnails/a51a8695ea38056db868edc178b4c1ab.jpg', 1, '2026-07-21 14:20:50'),
(51, 17, 'news/6af6842084043346b62b8a1a0b0cdd7f.jpg', 'thumbnails/6af6842084043346b62b8a1a0b0cdd7f.jpg', 2, '2026-07-21 14:20:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reacciones`
--

DROP TABLE IF EXISTS `reacciones`;
CREATE TABLE IF NOT EXISTS `reacciones` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_noticia` int UNSIGNED NOT NULL,
  `id_usuario` int UNSIGNED DEFAULT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'like',
  `estado` enum('pendiente','aprobado','rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reaccion_ip_noticia_tipo` (`id_noticia`,`ip_address`,`tipo`),
  KEY `fk_reacciones_usuario` (`id_usuario`),
  KEY `idx_reacciones_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reacciones`
--

INSERT INTO `reacciones` (`id`, `id_noticia`, `id_usuario`, `tipo`, `estado`, `ip_address`, `created_at`) VALUES
(1, 1, NULL, 'like', 'aprobado', '190.10.20.1', '2026-07-01 10:05:00'),
(2, 1, NULL, 'like', 'aprobado', '190.10.20.2', '2026-07-01 12:00:00'),
(3, 1, NULL, 'like', 'aprobado', '190.10.20.3', '2026-07-01 18:00:00'),
(4, 2, NULL, 'like', 'aprobado', '190.10.20.4', '2026-07-02 15:10:00'),
(5, 3, NULL, 'like', 'aprobado', '190.10.20.5', '2026-07-03 10:00:00'),
(6, 3, NULL, 'like', 'aprobado', '190.10.20.6', '2026-07-03 11:00:00'),
(7, 1, NULL, 'like', 'aprobado', '::1', '2026-07-21 05:48:50'),
(8, 15, NULL, 'like', 'aprobado', '::1', '2026-07-21 12:31:48'),
(9, 14, NULL, 'like', 'aprobado', '::1', '2026-07-21 13:13:28'),
(10, 14, 4, 'eco', 'aprobado', '::1', '2026-07-21 14:02:22'),
(11, 17, 1, 'like', 'aprobado', '::1', '2026-07-21 14:21:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash bcrypt (password_hash)',
  `rol` enum('admin','editor','supervisor','usuario') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usuario',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `intentos_fallidos` int UNSIGNED NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  KEY `idx_usuarios_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `activo`, `intentos_fallidos`, `bloqueado_hasta`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@hotmail.com', '$2y$12$2R6Q0ZoLRvHIu1LgP6Be0u.5PNY10PNhb8qeBJDv8p1J0BOw.ooHa', 'admin', 1, 0, NULL, '2026-07-20 16:22:50', NULL),
(2, 'Editor Demo', 'editor@hotmail.com', '$2y$12$MIqyu1vJobWc0Qlrzath8OF9Al4IjanbpEkAvuwzd3xQwtl660LQ6', 'editor', 1, 0, NULL, '2026-07-20 16:22:50', NULL),
(3, 'Supervisor Demo', 'supervisor@hotmail.com', '$2y$12$TyRuZQlFAiAUHlIyGrjXDezzLUyE6/GojyyDvYj7O3uORbVuAM5Ry', 'supervisor', 1, 0, NULL, '2026-07-20 16:22:50', NULL),
(4, 'Luis Fernando', 'luifer11@gmail.com', '$2y$12$wre/g7F/9Hqm2dDvCMuujOStcYsiAwRgT/Cmn2X3dzmq4ShAJXQXi', 'usuario', 1, 0, NULL, '2026-07-21 14:02:11', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitas`
--

DROP TABLE IF EXISTS `visitas`;
CREATE TABLE IF NOT EXISTS `visitas` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitas_session` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `visitas`
--

INSERT INTO `visitas` (`id`, `session_id`, `ip_address`, `created_at`) VALUES
(1, 'seed_session_1', '190.10.20.1', '2026-07-01 10:00:00'),
(2, 'seed_session_2', '190.10.20.2', '2026-07-02 11:00:00'),
(3, 'seed_session_3', '190.10.20.3', '2026-07-03 12:00:00'),
(4, 'seed_session_4', '190.10.20.4', '2026-07-04 13:00:00'),
(5, 'seed_session_5', '190.10.20.5', '2026-07-05 14:00:00'),
(6, 'b47d2bb948388ee48a9617c299b62356', '::1', '2026-07-20 21:23:00'),
(7, '34575b7183ac29bc52672e3ca673c4c7', '::1', '2026-07-20 21:43:17'),
(8, 'baf154c7a3d51044e29ac8d9ff1b6b84', '::1', '2026-07-20 21:45:51'),
(9, 'a7993edd1cdb69530fd2b4d2b2f4af4e', '::1', '2026-07-20 21:51:57'),
(10, 'fff494f2019099b094ab12ae4a4f1444', '::1', '2026-07-20 21:54:07'),
(11, '6f9d2f218a81b7bf3c6e57e395edc11e', '::1', '2026-07-20 22:04:26'),
(12, '23b5d2e48ca60ba419ba8abfcc06e0bd', '::1', '2026-07-21 06:12:31'),
(13, '1e41c82ee595cb0dbefdd6b80df56bb3', '::1', '2026-07-21 06:14:52'),
(14, '9d66aff5d82931920d25e36848cc80c8', '::1', '2026-07-21 12:14:14'),
(15, '5021d09c902828c95d20219bab21c50e', '::1', '2026-07-21 13:07:59'),
(16, 'eedd8247f184721612ddb0731331eb06', '::1', '2026-07-21 13:12:10'),
(17, '7a7a147fb1532a44f0475e4d43f66c6b', '::1', '2026-07-21 14:02:11'),
(18, '5aa88f74f4e2488234729182ba98fd11', '::1', '2026-07-21 14:03:38'),
(19, '3ed10ae04c9079274f5302c10e1dd1c1', '::1', '2026-07-21 14:04:01'),
(20, '4dd94575a9c6957691488a8381261c6d', '::1', '2026-07-21 14:09:54'),
(21, '87f217a32ab1841c9727284c23eabe5c', '::1', '2026-07-21 14:14:53'),
(22, '70aacc077309124c923a7aaec7f6c08e', '::1', '2026-07-21 14:21:02'),
(23, 'ff58a9791d96ba3e2477fc969b4e7c4a', '::1', '2026-07-21 14:25:29');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias` ADD FULLTEXT KEY `ftx_noticias_titulo` (`titulo`);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `fk_comentarios_admin` FOREIGN KEY (`id_usuario_admin`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_comentarios_noticia` FOREIGN KEY (`id_noticia`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comentarios_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `fk_noticias_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_noticias_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `noticia_imagenes`
--
ALTER TABLE `noticia_imagenes`
  ADD CONSTRAINT `fk_imagenes_noticia` FOREIGN KEY (`id_noticia`) REFERENCES `noticias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reacciones`
--
ALTER TABLE `reacciones`
  ADD CONSTRAINT `fk_reacciones_noticia` FOREIGN KEY (`id_noticia`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reacciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;