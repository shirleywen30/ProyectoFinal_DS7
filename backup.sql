-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 21-07-2026 a las 15:06:28
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
