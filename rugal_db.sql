-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 02-04-2026 a las 20:45:12
-- Versión del servidor: 8.3.0
-- Versión de PHP: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `rugal_db`
--

DELIMITER $$
--
-- Funciones
--
DROP FUNCTION IF EXISTS `es_premium`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `es_premium` (`p_user_id` INT) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE v_premium TINYINT(1);
    
    SELECT COUNT(*) > 0 INTO v_premium
    FROM suscripciones
    WHERE user_id = p_user_id
    AND estado = 'activa'
    AND fecha_fin >= CURDATE();
    
    RETURN v_premium;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

DROP TABLE IF EXISTS `alertas`;
CREATE TABLE IF NOT EXISTS `alertas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seguimiento_id` int NOT NULL,
  `tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sintomas` json NOT NULL,
  `explicacion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `seguimiento_id` (`seguimiento_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aliados`
--

DROP TABLE IF EXISTS `aliados`;
CREATE TABLE IF NOT EXISTS `aliados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `nombre_local` varchar(200) NOT NULL,
  `descripcion` text,
  `direccion` varchar(255) DEFAULT NULL,
  `google_maps_url` varchar(600) DEFAULT NULL,
  `fotos_verificacion` text,
  `telefono` varchar(30) DEFAULT NULL,
  `horario` text,
  `precio_consulta` decimal(10,2) DEFAULT NULL,
  `servicios` text,
  `tipo_alimento` varchar(100) DEFAULT NULL,
  `razas_recomendadas` text,
  `foto_local` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `pendiente_verificacion` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `acepta_citas` tinyint(1) DEFAULT '1',
  `anticipo_requerido` int DEFAULT '50',
  `cuenta_banco` varchar(255) DEFAULT NULL,
  `titular_cuenta` varchar(255) DEFAULT NULL,
  `calificacion` decimal(3,1) DEFAULT '5.0',
  `total_calificaciones` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `aliados`
--

INSERT INTO `aliados` (`id`, `usuario_id`, `tipo`, `nombre_local`, `descripcion`, `direccion`, `google_maps_url`, `fotos_verificacion`, `telefono`, `horario`, `precio_consulta`, `servicios`, `tipo_alimento`, `razas_recomendadas`, `foto_local`, `activo`, `pendiente_verificacion`, `created_at`, `updated_at`, `acepta_citas`, `anticipo_requerido`, `cuenta_banco`, `titular_cuenta`, `calificacion`, `total_calificaciones`) VALUES
(13, 47, 'veterinaria', 'veterinaria@gmail.com', 'NOS ESPECIALIZAMOS EN PERROS \r\n', 'av 7 b oeste  19-154', NULL, '[\"uploads\\/aliados_verificacion\\/aliado_47_1775060166_0.jpeg\",\"uploads\\/aliados_verificacion\\/aliado_47_1775060166_1.jpeg\",\"uploads\\/aliados_verificacion\\/aliado_47_1775060166_2.jpeg\"]', NULL, '{\"Lunes\":{\"apertura\":\"08:00\",\"cierre\":\"18:00\",\"abierto\":\"1\"},\"Martes\":{\"apertura\":\"08:00\",\"cierre\":\"18:00\",\"abierto\":\"1\"},\"Miércoles\":{\"apertura\":\"08:00\",\"cierre\":\"18:00\",\"abierto\":\"1\"},\"Jueves\":{\"apertura\":\"08:00\",\"cierre\":\"18:00\",\"abierto\":\"1\"},\"Viernes\":{\"apertura\":\"08:00\",\"cierre\":\"18:00\",\"abierto\":\"1\"},\"Sábado\":{\"apertura\":\"08:00\",\"cierre\":\"18:00\",\"abierto\":\"1\"},\"Domingo\":{\"apertura\":\"08:00\",\"cierre\":\"18:00\",\"abierto\":\"1\"}}', 30000.00, 'uploads/aliados/vet_69cd6988db63c.jpeg', '', '', '[]', 1, 0, '2026-04-01 16:16:06', '2026-04-01 18:52:56', 1, 50, 'nequi', '3167197604', 5.0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bloqueos_horario`
--

DROP TABLE IF EXISTS `bloqueos_horario`;
CREATE TABLE IF NOT EXISTS `bloqueos_horario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `veterinaria_id` (`veterinaria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canjes`
--

DROP TABLE IF EXISTS `canjes`;
CREATE TABLE IF NOT EXISTS `canjes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `recompensa_id` int NOT NULL,
  `aliado_id` int DEFAULT NULL,
  `puntos_gastados` int NOT NULL,
  `estado` enum('pendiente','activo','usado','expirado') DEFAULT 'pendiente',
  `codigo_canje` varchar(20) DEFAULT NULL,
  `fecha_expiracion` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usado_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_canje` (`codigo_canje`),
  KEY `recompensa_id` (`recompensa_id`),
  KEY `idx_codigo` (`codigo_canje`),
  KEY `idx_user_estado` (`user_id`,`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

DROP TABLE IF EXISTS `citas`;
CREATE TABLE IF NOT EXISTS `citas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `mascota_id` int NOT NULL,
  `veterinaria_id` int NOT NULL,
  `servicio_id` int DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `tipo_cita` varchar(50) DEFAULT 'consulta',
  `motivo` text,
  `estado` varchar(20) DEFAULT 'pendiente',
  `precio_total` decimal(10,2) DEFAULT NULL,
  `anticipo_pagado` decimal(10,2) DEFAULT '0.00',
  `anticipo_requerido` decimal(10,2) DEFAULT NULL,
  `porcentaje_anticipo` int DEFAULT '50',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `comprobante_pago` varchar(255) DEFAULT NULL,
  `es_manual` tinyint(1) DEFAULT '0',
  `notas_usuario` text,
  `notas_veterinaria` text,
  `diagnostico` text,
  `tratamiento` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mascota_id` (`mascota_id`),
  KEY `veterinaria_id` (`veterinaria_id`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `idx_estado` (`estado`),
  KEY `idx_user` (`user_id`),
  KEY `fk_citas_servicio` (`servicio_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `user_id`, `mascota_id`, `veterinaria_id`, `servicio_id`, `fecha_hora`, `tipo_cita`, `motivo`, `estado`, `precio_total`, `anticipo_pagado`, `anticipo_requerido`, `porcentaje_anticipo`, `metodo_pago`, `comprobante_pago`, `es_manual`, `notas_usuario`, `notas_veterinaria`, `diagnostico`, `tratamiento`, `created_at`, `updated_at`) VALUES
(21, 48, 29, 13, 14, '2026-04-03 13:00:00', 'consulta', 'DSADWASDAWD', 'confirmada', 50000.00, 0.00, 25000.00, 50, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-04-02 19:06:50', '2026-04-02 19:43:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_veterinaria`
--

DROP TABLE IF EXISTS `clientes_veterinaria`;
CREATE TABLE IF NOT EXISTS `clientes_veterinaria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `veterinario_id` int NOT NULL,
  `mascota_id` int NOT NULL,
  `user_id` int NOT NULL,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes_veterinaria`
--

INSERT INTO `clientes_veterinaria` (`id`, `veterinario_id`, `mascota_id`, `user_id`, `fecha_registro`) VALUES
(1, 47, 29, 48, '2026-04-02 14:16:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

DROP TABLE IF EXISTS `comentarios`;
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `publicacion_id` int NOT NULL,
  `contenido` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_publicacion` (`publicacion_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_educacion`
--

DROP TABLE IF EXISTS `comentarios_educacion`;
CREATE TABLE IF NOT EXISTS `comentarios_educacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contenido_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comentario` text NOT NULL,
  `calificacion` tinyint DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contenido_id` (`contenido_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clave`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`, `last_updated`) VALUES
('site_name', 'RUGAL', '2026-01-23 18:27:20'),
('support_email', 'a4ntiag0@gmail.com', '2026-01-23 18:34:30'),
('maintenance_mode', '1', '2026-01-26 16:36:37'),
('points_register', '0', '2026-01-23 18:34:30'),
('points_post', '0', '2026-01-23 18:34:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas_veterinarias`
--

DROP TABLE IF EXISTS `consultas_veterinarias`;
CREATE TABLE IF NOT EXISTS `consultas_veterinarias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `veterinario_id` int NOT NULL,
  `user_id` int NOT NULL,
  `fecha` datetime NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `resumen` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mascota_id` (`mascota_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenido_educativo`
--

DROP TABLE IF EXISTS `contenido_educativo`;
CREATE TABLE IF NOT EXISTS `contenido_educativo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text,
  `paso_a_paso` text,
  `lista_necesidades` text,
  `tipo` enum('foto','video') NOT NULL DEFAULT 'foto',
  `categoria` enum('educacion','alimentacion','juegos','limpieza') DEFAULT 'educacion',
  `media_url` varchar(255) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `likes` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas_tienda`
--

DROP TABLE IF EXISTS `detalle_ventas_tienda`;
CREATE TABLE IF NOT EXISTS `detalle_ventas_tienda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diagnosticos`
--

DROP TABLE IF EXISTS `diagnosticos`;
CREATE TABLE IF NOT EXISTS `diagnosticos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `consulta_id` int NOT NULL,
  `diagnostico` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_diag_consulta` (`consulta_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad_veterinaria`
--

DROP TABLE IF EXISTS `disponibilidad_veterinaria`;
CREATE TABLE IF NOT EXISTS `disponibilidad_veterinaria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int NOT NULL,
  `dia_semana` tinyint NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `duracion_cita` int DEFAULT '30',
  `cupo_maximo` int DEFAULT '10',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_veterinaria` (`veterinaria_id`),
  KEY `idx_dia` (`dia_semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_animo`
--

DROP TABLE IF EXISTS `estado_animo`;
CREATE TABLE IF NOT EXISTS `estado_animo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `estado` varchar(50) NOT NULL,
  `notas` text,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mascota_id` (`mascota_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_diario`
--

DROP TABLE IF EXISTS `estado_diario`;
CREATE TABLE IF NOT EXISTS `estado_diario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `mascota_id` int NOT NULL,
  `fecha` date NOT NULL,
  `pregunta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `respuesta` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fecha_user` (`fecha`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estado_diario`
--

INSERT INTO `estado_diario` (`id`, `user_id`, `mascota_id`, `fecha`, `pregunta`, `respuesta`, `created_at`) VALUES
(1, 2, 1, '2026-01-06', NULL, 'excelente', '2026-01-06 03:50:50'),
(2, 2, 1, '2026-01-09', NULL, 'excelente', '2026-01-09 02:24:35'),
(3, 8, 3, '2026-01-09', NULL, 'bien', '2026-01-09 15:51:56'),
(4, 8, 3, '2026-01-10', NULL, 'bien', '2026-01-10 01:15:04'),
(5, 8, 3, '2026-01-19', NULL, 'bien', '2026-01-19 03:44:43'),
(6, 8, 6, '2026-01-19', NULL, 'bien', '2026-01-19 16:03:23'),
(7, 8, 6, '2026-01-22', NULL, 'excelente', '2026-01-22 14:23:11'),
(8, 8, 6, '2026-01-23', NULL, 'excelente', '2026-01-23 13:04:42'),
(9, 8, 6, '2026-01-24', NULL, 'bien', '2026-01-24 16:44:56'),
(10, 8, 6, '2026-01-26', NULL, 'excelente', '2026-01-26 16:58:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_medico`
--

DROP TABLE IF EXISTS `historial_medico`;
CREATE TABLE IF NOT EXISTS `historial_medico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `fecha` date NOT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Consulta, Urgencia, Cirugía, Control',
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diagnostico` text COLLATE utf8mb4_unicode_ci,
  `tratamiento` text COLLATE utf8mb4_unicode_ci,
  `veterinario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clinica` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `archivos` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON con rutas de archivos',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mascota` (`mascota_id`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_medico`
--

INSERT INTO `historial_medico` (`id`, `mascota_id`, `fecha`, `tipo`, `motivo`, `diagnostico`, `tratamiento`, `veterinario`, `clinica`, `notas`, `archivos`, `created_at`) VALUES
(36, 3, '2026-01-19', 'Consulta', 'VISITA ', '', '', '', '', '', NULL, '2026-01-19 05:02:39'),
(59, 8, '2026-01-27', 'IA', 'Consulta al Asistente IA', 'el usuario diagnostico siento que dennis es gay del perro la fecha 27/01/2026', 'Respuesta de la IA: pienso lo mismo', 'IA RUGAL', 'Sistema RUGAL', 'Interacción IA: El usuario puso esto y la IA le respondió.', NULL, '2026-01-27 07:24:08'),
(60, 8, '2026-01-27', 'IA', 'Consulta al Asistente IA', 'el usuario diagnostico y mi perro no come del perro la fecha 27/01/2026', 'Respuesta de la IA: El chocolate puede ser muy toxico para los animales es muy toxico si el perro a consumido chocolate puede esperar a ver que comportamientos puede tomar el perro o si quieres estar mas seguro que es lo que te recomiendo llevalo a un veterinario', 'IA RUGAL', 'Sistema RUGAL', 'Interacción IA: El usuario puso esto y la IA le respondió.', NULL, '2026-01-27 07:24:31'),
(62, 6, '2026-01-27', 'IA', 'Consulta al Asistente IA', 'el usuario diagnostico HOLIIII del perro la fecha 27/01/2026', 'Respuesta de la IA: Buenas Soy el asesor virtual de RUGAL preguntame sobre el estado de tu mascota o que quieres saber', 'IA RUGAL', 'Sistema RUGAL', 'Interacción IA: El usuario puso esto y la IA le respondió.', NULL, '2026-01-27 20:59:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes`
--

DROP TABLE IF EXISTS `likes`;
CREATE TABLE IF NOT EXISTS `likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `publicacion_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_id`,`publicacion_id`),
  KEY `publicacion_id` (`publicacion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

DROP TABLE IF EXISTS `mascotas`;
CREATE TABLE IF NOT EXISTS `mascotas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `estado_salud` varchar(50) DEFAULT 'excelente',
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(50) DEFAULT 'perro',
  `raza` varchar(100) DEFAULT NULL,
  `edad` int DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `peso_promedio` decimal(5,2) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `sexo` varchar(10) DEFAULT NULL,
  `nivel_actividad` varchar(20) DEFAULT 'medio',
  `foto_perfil` varchar(255) DEFAULT NULL,
  `codigo_qr` varchar(100) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `esterilizado` tinyint(1) DEFAULT '0',
  `tamano` enum('pequeno','mediano','grande','gigante') DEFAULT 'mediano',
  `vive_en` enum('apartamento','casa','finca') DEFAULT 'casa',
  `alergias` text,
  `enfermedades_previas` text,
  `alimentacion_actual` text,
  `peso_ideal` decimal(5,2) DEFAULT NULL,
  `ultima_actualizacion_salud` datetime DEFAULT NULL,
  `especie` varchar(50) DEFAULT 'perro',
  `edad_anios` int DEFAULT '0',
  `edad_meses` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_qr` (`codigo_qr`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`id`, `user_id`, `estado_salud`, `nombre`, `tipo`, `raza`, `edad`, `peso`, `peso_promedio`, `color`, `sexo`, `nivel_actividad`, `foto_perfil`, `codigo_qr`, `estado`, `created_at`, `updated_at`, `esterilizado`, `tamano`, `vive_en`, `alergias`, `enfermedades_previas`, `alimentacion_actual`, `peso_ideal`, `ultima_actualizacion_salud`, `especie`, `edad_anios`, `edad_meses`) VALUES
(29, 48, 'excelente', 'RUGAL', 'perro', 'american bully', 1, 31.00, 31.00, NULL, 'macho', 'medio', 'pet_1775066699_5b81e043.jpeg', 'RUGAL-53A60A7DC8', 'activo', '2026-04-01 18:04:59', '2026-04-01 18:04:59', 0, 'mediano', 'casa', NULL, NULL, NULL, NULL, NULL, 'perro', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas_salud`
--

DROP TABLE IF EXISTS `mascotas_salud`;
CREATE TABLE IF NOT EXISTS `mascotas_salud` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `tipo` enum('vacuna','desparasitacion','bano','consulta','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_evento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_realizado` date NOT NULL,
  `proxima_fecha` date DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `veterinaria_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mascota` (`mascota_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

DROP TABLE IF EXISTS `pagos`;
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `suscripcion_id` int DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `estado` enum('pendiente','completado','fallido','reembolsado') DEFAULT 'pendiente',
  `referencia_pago` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `suscripcion_id` (`suscripcion_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peso_historial`
--

DROP TABLE IF EXISTS `peso_historial`;
CREATE TABLE IF NOT EXISTS `peso_historial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `fecha` date NOT NULL,
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mascota_id` (`mascota_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `peso_historial`
--

INSERT INTO `peso_historial` (`id`, `mascota_id`, `peso`, `fecha`, `notas`, `created_at`) VALUES
(31, 29, 31.00, '2026-04-01', NULL, '2026-04-01 18:04:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_premium`
--

DROP TABLE IF EXISTS `planes_premium`;
CREATE TABLE IF NOT EXISTS `planes_premium` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text,
  `precio` decimal(10,2) NOT NULL,
  `duracion_dias` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `planes_premium`
--

INSERT INTO `planes_premium` (`id`, `nombre`, `descripcion`, `precio`, `duracion_dias`, `activo`, `created_at`) VALUES
(1, 'Mensual', 'Acceso completo por 1 mes', 12500.00, 30, 1, '2026-01-05 20:51:20'),
(2, 'Trimestral', 'Acceso completo por 3 meses (10% descuento)', 34500.00, 90, 1, '2026-01-05 20:51:20'),
(3, 'Semestral', 'Acceso completo por 6 meses (15% descuento)', 66000.00, 180, 1, '2026-01-05 20:51:20'),
(4, 'Anual', 'Acceso completo por 1 año (20% descuento)', 144000.00, 365, 1, '2026-01-05 20:51:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_salud`
--

DROP TABLE IF EXISTS `planes_salud`;
CREATE TABLE IF NOT EXISTS `planes_salud` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `veterinaria_id` int DEFAULT NULL,
  `generado_por` varchar(20) DEFAULT 'ia',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `objetivo` text,
  `plan_alimentacion` text,
  `plan_ejercicio` text,
  `vacunas_pendientes` text,
  `examenes_recomendados` text,
  `medicamentos` text,
  `frecuencia_revision` varchar(50) DEFAULT NULL,
  `notas` text,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `plan_bienestar_mental` text,
  `plan_higiene` text,
  PRIMARY KEY (`id`),
  KEY `veterinaria_id` (`veterinaria_id`),
  KEY `idx_mascota` (`mascota_id`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_salud_general`
--

DROP TABLE IF EXISTS `planes_salud_general`;
CREATE TABLE IF NOT EXISTS `planes_salud_general` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `plan_data` json NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mascota_plan` (`mascota_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_salud_mensual`
--

DROP TABLE IF EXISTS `planes_salud_mensual`;
CREATE TABLE IF NOT EXISTS `planes_salud_mensual` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `user_id` int NOT NULL,
  `mes` int NOT NULL,
  `anio` int NOT NULL,
  `datos_json` longtext NOT NULL,
  `recomendaciones_json` longtext NOT NULL,
  `nivel_alerta` varchar(20) DEFAULT 'verde',
  `alertas_json` longtext,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `health_score` int DEFAULT '85' COMMENT 'Health score percentage (0-100)',
  `last_health_update` timestamp NULL DEFAULT NULL COMMENT 'Last time health score was updated',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_mascota_mes` (`mascota_id`,`mes`,`anio`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_salud_mensual_tareas`
--

DROP TABLE IF EXISTS `plan_salud_mensual_tareas`;
CREATE TABLE IF NOT EXISTS `plan_salud_mensual_tareas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `plan_id` int NOT NULL,
  `tipo_tarea` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `categoria` varchar(50) DEFAULT 'general',
  `icono` varchar(50) DEFAULT 'fa-star',
  `completada` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plan_fecha` (`plan_id`,`fecha`),
  KEY `idx_completada` (`completada`)
) ENGINE=InnoDB AUTO_INCREMENT=1803 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preconsultas`
--

DROP TABLE IF EXISTS `preconsultas`;
CREATE TABLE IF NOT EXISTS `preconsultas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `cita_id` int DEFAULT NULL,
  `generado_por` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'sistema',
  `dias_considerados` int DEFAULT '7',
  `resumen` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `datos` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mascota_id` (`mascota_id`),
  KEY `cita_id` (`cita_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_tienda`
--

DROP TABLE IF EXISTS `productos_tienda`;
CREATE TABLE IF NOT EXISTS `productos_tienda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tienda_id` int NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text,
  `categoria` varchar(100) DEFAULT NULL,
  `subcategoria` varchar(100) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `precio_oferta` decimal(10,2) DEFAULT NULL,
  `stock` int DEFAULT '0',
  `imagen` varchar(255) DEFAULT NULL,
  `peso` varchar(50) DEFAULT NULL,
  `razas_recomendadas` text,
  `activo` tinyint(1) DEFAULT '1',
  `destacado` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tienda_id` (`tienda_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_veterinaria`
--

DROP TABLE IF EXISTS `productos_veterinaria`;
CREATE TABLE IF NOT EXISTS `productos_veterinaria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text,
  `categoria` varchar(100) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `stock` int DEFAULT '0',
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `veterinaria_id` (`veterinaria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos_veterinaria`
--

INSERT INTO `productos_veterinaria` (`id`, `veterinaria_id`, `nombre`, `descripcion`, `categoria`, `precio`, `stock`, `imagen`, `activo`, `created_at`, `updated_at`) VALUES
(12, 13, 'NEXGARD', '\r\n', 'Medicamentos', 65000.00, 20, 'uploads/productos_vet/prod_69cebe11e7e0a.jfif', 1, '2026-04-02 05:35:57', '2026-04-02 19:06:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

DROP TABLE IF EXISTS `promociones`;
CREATE TABLE IF NOT EXISTS `promociones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aliado_id` int NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text,
  `descuento_porcentaje` decimal(5,2) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `codigo_cupon` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `aliado_id` (`aliado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones`
--

DROP TABLE IF EXISTS `publicaciones`;
CREATE TABLE IF NOT EXISTS `publicaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `media_type` enum('image','video') DEFAULT 'image',
  `mascota_id` int DEFAULT NULL,
  `contenido` text NOT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `puntos_ganados` int DEFAULT '0',
  `likes` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mascota_id` (`mascota_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones_likes`
--

DROP TABLE IF EXISTS `publicaciones_likes`;
CREATE TABLE IF NOT EXISTS `publicaciones_likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `publicacion_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_id`,`publicacion_id`),
  KEY `idx_publicacion_likes` (`publicacion_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `publicaciones_likes`
--

INSERT INTO `publicaciones_likes` (`id`, `user_id`, `publicacion_id`, `created_at`) VALUES
(1, 10, 7, '2026-01-22 18:19:11'),
(2, 8, 7, '2026-01-22 18:20:43'),
(3, 8, 2, '2026-01-22 18:22:28'),
(4, 8, 9, '2026-01-23 00:34:23'),
(5, 8, 11, '2026-01-23 04:58:18'),
(6, 8, 10, '2026-01-23 04:58:59'),
(7, 8, 8, '2026-01-23 04:59:00'),
(8, 12, 13, '2026-01-27 04:02:11'),
(9, 12, 12, '2026-01-27 04:02:13'),
(10, 12, 10, '2026-01-27 04:02:14'),
(12, 13, 19, '2026-02-03 22:17:03'),
(13, 13, 18, '2026-02-08 07:38:10'),
(14, 13, 20, '2026-02-08 07:38:35'),
(15, 22, 21, '2026-02-11 07:23:49'),
(16, 25, 21, '2026-02-18 17:35:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_historial`
--

DROP TABLE IF EXISTS `puntos_historial`;
CREATE TABLE IF NOT EXISTS `puntos_historial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `puntos` int NOT NULL,
  `tipo` enum('ganado','canjeado') NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `tarea_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `puntos_historial`
--

INSERT INTO `puntos_historial` (`id`, `user_id`, `puntos`, `tipo`, `descripcion`, `tarea_id`, `created_at`) VALUES
(41, 48, 5, 'ganado', 'Tarea aprobada: Bienvenido', 30, '2026-04-02 05:54:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recomendaciones`
--

DROP TABLE IF EXISTS `recomendaciones`;
CREATE TABLE IF NOT EXISTS `recomendaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('salud','nutricion','ejercicio','curiosidad') COLLATE utf8mb4_unicode_ci DEFAULT 'curiosidad',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recomendaciones`
--

INSERT INTO `recomendaciones` (`id`, `titulo`, `contenido`, `categoria`, `activo`) VALUES
(1, 'Hidratación', 'Asegúrate de cambiar el agua de tu mascota al menos 2 veces al día.', 'curiosidad', 1),
(2, 'Paseos', '30 minutos de caminata diaria mejoran significativamente el humor de tu perro.', 'curiosidad', 1),
(3, 'Cepillado', 'Cepillar a tu gato ayuda a prevenir bolas de pelo y estrés.', 'curiosidad', 1),
(4, 'Dientes', 'La salud dental es vital. Revisa sus encías una vez por semana.', 'curiosidad', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recompensas`
--

DROP TABLE IF EXISTS `recompensas`;
CREATE TABLE IF NOT EXISTS `recompensas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `partner_name` varchar(100) DEFAULT 'RUGAL',
  `descripcion` text NOT NULL,
  `puntos_requeridos` int NOT NULL,
  `tipo` enum('producto','servicio','premium') NOT NULL,
  `tipo_acceso` enum('free','premium') DEFAULT 'free',
  `aliado_id` int DEFAULT NULL,
  `stock` int DEFAULT '-1',
  `fecha_limite` datetime DEFAULT NULL,
  `ubicacion_canje` text,
  `alcance_tipo` enum('global','tipo_aliado','especificos') DEFAULT 'global',
  `alcance_valor` text,
  `imagen` varchar(255) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `producto_id` int DEFAULT NULL,
  `producto_tabla` varchar(50) DEFAULT NULL,
  `precio_original` decimal(10,2) DEFAULT NULL,
  `precio_oferta` decimal(10,2) DEFAULT NULL,
  `porcentaje_descuento` int DEFAULT NULL,
  `es_gratis` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aliado_id` (`aliado_id`),
  KEY `idx_puntos` (`puntos_requeridos`),
  KEY `idx_activa` (`activa`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `recompensas`
--

INSERT INTO `recompensas` (`id`, `titulo`, `partner_name`, `descripcion`, `puntos_requeridos`, `tipo`, `tipo_acceso`, `aliado_id`, `stock`, `fecha_limite`, `ubicacion_canje`, `alcance_tipo`, `alcance_valor`, `imagen`, `activa`, `created_at`, `producto_id`, `producto_tabla`, `precio_original`, `precio_oferta`, `porcentaje_descuento`, `es_gratis`) VALUES
(1, 'Pelota para Perro', 'PetShop Peluditos', 'Pelota de goma resistente', 50, 'producto', 'free', NULL, 99, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(2, 'Juguete para Gato', 'PetShop Peluditos', 'Ratón con catnip', 50, 'producto', 'free', NULL, 99, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(3, 'Snacks Naturales', 'PetShop Peluditos', 'Paquete de snacks saludables', 50, 'producto', 'free', NULL, 100, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(4, 'Collar Decorativo', 'PetShop Peluditos', 'Collar ajustable con diseño', 50, 'producto', 'free', NULL, 80, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(5, 'Consulta Veterinaria Gratis', 'VetCare Clinica', 'Consulta general en veterinarias aliadas', 100, 'servicio', 'free', NULL, 50, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(6, 'Baño y Peluquería', 'VetCare Clinica', 'Servicio completo de aseo', 100, 'servicio', 'free', NULL, 30, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(7, 'Limpieza Dental Básica', 'VetCare Clinica', 'Limpieza dental profesional', 100, 'servicio', 'free', NULL, 20, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(8, 'Alimento Premium 5kg', 'PetShop Peluditos', 'Bolsa de alimento de alta calidad', 200, 'producto', 'free', NULL, 50, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(9, 'Cama para Mascota', 'PetShop Peluditos', 'Cama acolchada tamaño mediano', 200, 'producto', 'free', NULL, 20, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(10, 'Kit de Aseo Completo', 'PetShop Peluditos', 'Shampoo, cepillo y cortauñas', 200, 'producto', 'free', NULL, 40, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(11, 'Membresía Premium 1 Mes', 'RUGAL', 'Acceso a funciones premium', 500, 'premium', 'free', NULL, -1, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(12, 'Caja Sorpresa Mensual', 'PetShop Peluditos', 'Productos variados para tu mascota', 500, 'producto', 'free', NULL, 10, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(13, 'Mochila Transportadora', 'PetShop Peluditos', 'Mochila cómoda para transporte', 500, 'producto', 'free', NULL, 15, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(14, 'Cirugía Menor Gratis', 'VetCare Clinica', 'Esterilización u otra cirugía menor', 1000, 'servicio', 'free', NULL, 5, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(15, 'Plan de Vacunación Completo', 'VetCare Clinica', 'Todas las vacunas del año', 1000, 'servicio', 'free', NULL, 10, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(16, 'Paquete VIP Anual', 'RUGAL', 'Beneficios exclusivos por 1 año', 1000, 'premium', 'free', NULL, -1, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-05 19:52:41', NULL, NULL, NULL, NULL, NULL, 0),
(17, 'CHUNKY', 'RUGAL', 'DASDASDASDA', 40, 'producto', 'free', NULL, 50, NULL, NULL, 'especificos', '3', NULL, 0, '2026-01-06 05:00:58', NULL, NULL, NULL, NULL, NULL, 0),
(18, 'Test Reward - Normal', 'RUGAL', 'A normal reward.', 100, 'producto', 'free', NULL, -1, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-23 17:37:33', NULL, NULL, NULL, NULL, NULL, 0),
(19, 'Test Reward - Limited Time', 'RUGAL', 'Expiring soon!', 500, 'servicio', 'free', NULL, 10, '2026-01-25 17:37:33', 'Clinica Veterinaria Central', 'global', NULL, NULL, 0, '2026-01-23 17:37:33', NULL, NULL, NULL, NULL, NULL, 0),
(20, 'Test Reward - Expired', 'RUGAL', 'Already expired.', 50, 'producto', 'free', NULL, 5, '2026-01-22 17:37:33', NULL, 'global', NULL, NULL, 0, '2026-01-23 17:37:33', NULL, NULL, NULL, NULL, NULL, 0),
(21, 'Test Reward - Low Stock', 'RUGAL', 'Only 1 left!', 1000, 'premium', 'free', NULL, 0, NULL, NULL, 'global', NULL, NULL, 0, '2026-01-23 17:37:33', NULL, NULL, NULL, NULL, NULL, 0),
(22, 'Shampoo Medicado', 'RUGAL', 'LLEVA TU SHAMPOO MEDICADO PARA TENER A TU PERRO CON UNA PIEL SALUDABLE Y BRILLANTE CON DESCUENTO ', 100, 'producto', 'free', NULL, 1, '2026-01-31 18:00:00', 'av 7 b oeste #19-154', 'especificos', '1', NULL, 0, '2026-01-25 20:13:59', 4, 'productos_veterinaria', NULL, NULL, NULL, 0),
(23, 'Descuento 10%', 'RUGAL', 'LLeva tu shampoo medicado para tu perro con el 10% de descuento en la tienda terron ', 50, '', 'free', NULL, 4, '2026-01-29 00:00:00', 'av 7 b oeste #19-154', 'especificos', '1', NULL, 0, '2026-01-26 16:56:43', 4, 'productos_veterinaria', 28000.00, 25200.00, 10, 0),
(24, 'CHUNKY', 'RUGAL', 'LLEVA 4 KILOS DE CHUNKY AL 30% DE DESCUENTO COMPLETA TUS TAREAS PARA OBTENER TU BENEFICIO\r\nRECUERDA QUE SOLO HAY UN STOCK \r\n', 0, 'producto', 'free', NULL, 9, NULL, 'av 7 b oeste #19-154', 'global', NULL, NULL, 0, '2026-02-24 04:35:06', NULL, NULL, 30000.00, NULL, NULL, 1),
(25, 'DESCUENTAZO', 'RUGAL', 'DASDWAD', 85, 'producto', 'free', NULL, -1, NULL, 'av 7 b oeste #19-154', 'especificos', '6,7', NULL, 0, '2026-02-24 23:04:37', NULL, 'productos_veterinaria', NULL, NULL, NULL, 0),
(26, 'Call Of dutty', 'RUGAL', 'asdawdsdaw', 5, 'producto', 'premium', NULL, -1, NULL, 'av 7 b oeste #19-154', 'global', NULL, NULL, 0, '2026-02-25 00:26:06', NULL, NULL, 15000.00, 10000.00, 5, 0),
(27, 'Call Of dutty', 'RUGAL', 'dsadsadaw', 30, 'producto', 'free', NULL, 3, NULL, '', 'tipo_aliado', 'veterinaria', NULL, 0, '2026-02-25 00:52:56', 10, 'productos_veterinaria', 80000.00, 56000.00, 30, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recordatorios`
--

DROP TABLE IF EXISTS `recordatorios`;
CREATE TABLE IF NOT EXISTS `recordatorios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `mascota_id` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_programada` datetime NOT NULL,
  `tipo` enum('salud','evento','otro') COLLATE utf8mb4_unicode_ci DEFAULT 'salud',
  `estado` enum('pendiente','completado','ignorado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_recordatorios_user` (`user_id`),
  KEY `fk_recordatorios_mascota` (`mascota_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recordatorios_plan`
--

DROP TABLE IF EXISTS `recordatorios_plan`;
CREATE TABLE IF NOT EXISTS `recordatorios_plan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `plan_id` int NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_programada` date NOT NULL,
  `completado` tinyint(1) DEFAULT '0',
  `fecha_completado` date DEFAULT NULL,
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plan` (`plan_id`),
  KEY `idx_fecha` (`fecha_programada`),
  KEY `idx_completado` (`completado`)
) ENGINE=InnoDB AUTO_INCREMENT=883 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimientos_diarios`
--

DROP TABLE IF EXISTS `seguimientos_diarios`;
CREATE TABLE IF NOT EXISTS `seguimientos_diarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mascota_id` int NOT NULL,
  `user_id` int NOT NULL,
  `fecha` date NOT NULL,
  `datos` json NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mascota_id` (`mascota_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `seguimientos_diarios`
--

INSERT INTO `seguimientos_diarios` (`id`, `mascota_id`, `user_id`, `fecha`, `datos`, `observaciones`, `created_at`) VALUES
(1, 14, 22, '2026-02-19', '{\"agua\": \"Normal (Bebe agua regularmente)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Normal (Come con normalidad)\", \"prurito\": true, \"actividad\": \"Normal (Comportamiento energético esperado)\"}', 'Normal pero se esta rascando normalmente ', '2026-02-19 00:50:10'),
(2, 17, 25, '2026-02-19', '{\"agua\": \"Normal (Bebe agua regularmente)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Hiporexia (Come menos que lo usual)\", \"actividad\": \"Normal (Comportamiento energético esperado)\"}', '', '2026-02-19 00:53:10'),
(3, 17, 25, '2026-02-19', '{\"agua\": \"Normal (Bebe agua regularmente)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Normal (Come con normalidad)\", \"actividad\": \"Normal (Comportamiento energético esperado)\"}', '', '2026-02-19 00:59:49'),
(4, 14, 22, '2026-02-19', '{\"agua\": \"Normal (Bebe agua regularmente)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Normal (Come con normalidad)\", \"actividad\": \"Normal (Comportamiento energético esperado)\"}', '', '2026-02-19 01:08:39'),
(5, 17, 25, '2026-02-19', '{\"agua\": \"Normal (Bebe agua regularmente)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Hiporexia (Come menos que lo usual)\", \"actividad\": \"Hiperactivo (Exceso de movimiento, inquietud)\"}', '', '2026-02-19 01:27:53'),
(6, 14, 22, '2026-02-21', '{\"agua\": \"Normal (Bebe agua regularmente)\", \"animo\": \"Apático (Sin interés, desánimo)\", \"apetito\": \"Anorexia (No come o rechaza alimento)\", \"prurito\": true, \"actividad\": \"Letárgico/Deprimido (Falta de energía, debilidad)\"}', '', '2026-02-21 11:01:01'),
(7, 14, 22, '2026-02-23', '{\"agua\": \"Polidipsia (Bebe más agua que lo usual)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Normal (Come con normalidad)\", \"prurito\": true, \"actividad\": \"Hiperactivo (Exceso de movimiento, inquietud)\"}', '', '2026-02-23 18:59:47'),
(8, 14, 22, '2026-02-25', '{\"agua\": \"Normal (Bebe agua regularmente)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Normal (Come con normalidad)\", \"diarrea\": true, \"prurito\": true, \"vomitos\": true, \"actividad\": \"Normal (Comportamiento energético esperado)\"}', 'shdasdhiawdhiad', '2026-02-25 01:13:03'),
(9, 29, 48, '2026-04-01', '{\"agua\": \"Polidipsia (Bebe más agua que lo usual)\", \"animo\": \"Normal/Feliz (Comportamiento habitual positivo)\", \"apetito\": \"Hiporexia (Come menos que lo usual)\", \"prurito\": true, \"actividad\": \"Letárgico/Deprimido (Falta de energía, debilidad)\"}', '', '2026-04-01 13:12:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios_veterinaria`
--

DROP TABLE IF EXISTS `servicios_veterinaria`;
CREATE TABLE IF NOT EXISTS `servicios_veterinaria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `veterinaria_id` int NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text,
  `precio` decimal(10,2) DEFAULT NULL,
  `duracion_minutos` int DEFAULT '30',
  `icono` varchar(50) DEFAULT 'fa-paw',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `veterinaria_id` (`veterinaria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `servicios_veterinaria`
--

INSERT INTO `servicios_veterinaria` (`id`, `veterinaria_id`, `nombre`, `descripcion`, `precio`, `duracion_minutos`, `icono`, `activo`, `created_at`, `updated_at`) VALUES
(14, 13, 'CONSULTA GENERAL', '', 50000.00, 30, 'fa-stethoscope', 1, '2026-04-01 18:03:50', '2026-04-02 06:17:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

DROP TABLE IF EXISTS `suscripciones`;
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activa','expirada','cancelada') DEFAULT 'activa',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `monto_pagado` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  KEY `idx_user_estado` (`user_id`,`estado`),
  KEY `idx_fecha_fin` (`fecha_fin`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas_completadas`
--

DROP TABLE IF EXISTS `tareas_completadas`;
CREATE TABLE IF NOT EXISTS `tareas_completadas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tarea_id` int NOT NULL,
  `puntos_ganados` int NOT NULL,
  `completada_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `evidencia` varchar(255) DEFAULT NULL,
  `estado_validacion` enum('pendiente','aprobada','rechazada','revocada') DEFAULT 'aprobada',
  `comentario_admin` text,
  PRIMARY KEY (`id`),
  KEY `tarea_id` (`tarea_id`),
  KEY `idx_user_tarea` (`user_id`,`tarea_id`),
  KEY `idx_completada_at` (`completada_at`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `tareas_completadas`
--

INSERT INTO `tareas_completadas` (`id`, `user_id`, `tarea_id`, `puntos_ganados`, `completada_at`, `evidencia`, `estado_validacion`, `comentario_admin`) VALUES
(58, 48, 30, 5, '2026-04-02 05:54:28', 'evidencia_48_1775109268_69ce04944d8b0.jpeg', 'aprobada', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas_comunidad`
--

DROP TABLE IF EXISTS `tareas_comunidad`;
CREATE TABLE IF NOT EXISTS `tareas_comunidad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `puntos` int NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'normal',
  `tipo_acceso` enum('free','premium') DEFAULT 'free',
  `fecha_limite` datetime DEFAULT NULL,
  `icono` varchar(50) DEFAULT 'fa-star',
  `activa` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `requiere_evidencia` tinyint(1) DEFAULT '0',
  `tipo_evidencia` enum('foto','video','ambos') DEFAULT 'foto',
  `categoria` varchar(50) DEFAULT 'otros',
  `detalles` text,
  `video_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_activa` (`activa`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `tareas_comunidad`
--

INSERT INTO `tareas_comunidad` (`id`, `titulo`, `descripcion`, `puntos`, `tipo`, `tipo_acceso`, `fecha_limite`, `icono`, `activa`, `created_at`, `requiere_evidencia`, `tipo_evidencia`, `categoria`, `detalles`, `video_url`) VALUES
(1, 'Actualizar Diario de Salud', 'Registra cómo estuvo tu mascota hoy', 2, 'diaria', 'free', NULL, 'fa-book-medical', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(2, 'Registrar Actividad Física', 'Registra al menos 30 min de ejercicio', 3, 'diaria', 'free', NULL, 'fa-running', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(3, 'Dar Comida a Tiempo', 'Alimenta a tu mascota en horario', 1, 'diaria', 'free', NULL, 'fa-utensils', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(4, 'Subir Foto de Socialización', 'Comparte una foto de tu mascota socializando', 5, 'semanal', 'free', NULL, 'fa-camera', 0, '2026-01-05 19:52:41', 1, 'foto', 'otros', NULL, NULL),
(5, 'Completar 5 Días de Ejercicio', 'Registra ejercicio 5 días esta semana', 15, 'semanal', 'free', NULL, 'fa-medal', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(6, 'Participar en Comunidad', 'Publica o comenta en el feed', 10, 'semanal', 'free', NULL, 'fa-comments', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(7, 'Vacuna a Tiempo', 'Completa una vacuna programada', 10, 'salud', 'free', NULL, 'fa-syringe', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(8, 'Control de Peso Mensual', 'Registra el peso de tu mascota', 5, 'salud', 'free', NULL, 'fa-weight', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(9, 'Asistir a Cita', 'Asiste a una cita veterinaria agendada', 8, 'salud', 'free', NULL, 'fa-hospital', 0, '2026-01-05 19:52:41', 0, 'foto', 'otros', NULL, NULL),
(10, 'Cumpleaños de Mascota', 'Celebra el cumpleaños de tu mascota', 20, 'especial', 'free', NULL, 'fa-birthday-cake', 0, '2026-01-05 19:52:41', 1, 'foto', 'otros', NULL, NULL),
(11, 'Primera Vacuna', 'Completa la primera vacuna de tu mascota', 15, 'especial', 'free', NULL, 'fa-star', 0, '2026-01-05 19:52:41', 1, 'foto', 'otros', NULL, NULL),
(12, 'Perfil Completo', 'Completa el 100% del perfil de tu mascota', 25, 'especial', 'free', NULL, 'fa-check-circle', 0, '2026-01-05 19:52:41', 1, 'foto', 'otros', NULL, NULL),
(13, 'Invitar Amigo', 'Invita a un amigo a RUGAL', 30, 'especial', 'free', NULL, 'fa-user-plus', 0, '2026-01-05 19:52:41', 1, 'foto', 'otros', NULL, NULL),
(14, 'MK', 'MK', 1, 'semanal', 'free', NULL, 'fas fa-star', 0, '2026-01-06 03:18:40', 0, 'foto', 'otros', NULL, NULL),
(15, 'baño', 'maecon', 3, 'diaria', 'free', NULL, 'fas fa-star', 0, '2026-01-06 04:59:49', 1, 'foto', 'otros', NULL, NULL),
(16, 'baño', 'Baña a tu perro y mandanos un video de tu perro siendo bañado ', 3, 'especial', 'free', NULL, '', 0, '2026-01-22 03:12:46', 1, 'video', 'otros', NULL, NULL),
(17, 'Test Task - Normal', 'Just a normal task.', 50, 'normal', 'free', NULL, 'fas fa-star', 0, '2026-01-23 17:37:33', 0, 'foto', 'otros', NULL, NULL),
(18, 'Test Task - Daily', 'Do this every day.', 20, 'diaria', 'free', NULL, 'fas fa-star', 0, '2026-01-23 17:37:33', 0, 'foto', 'otros', NULL, NULL),
(19, 'Test Task - Time Limited', 'Complete before it expires!', 100, 'tiempo_limite', 'free', '2026-01-26 17:37:33', 'fas fa-star', 0, '2026-01-23 17:37:33', 0, 'foto', 'otros', NULL, NULL),
(20, 'Test Task - Expired', 'Too late.', 200, 'tiempo_limite', 'free', '2026-01-22 17:37:33', 'fas fa-star', 0, '2026-01-23 17:37:33', 0, 'foto', 'otros', NULL, NULL),
(21, 'ELECTRISISTA', 'dsadawd', 1, 'tiempo_limite', 'free', '2026-01-23 00:00:00', 'fas fa-star', 0, '2026-01-23 17:44:42', 1, 'foto', 'otros', NULL, NULL),
(22, 'manp', 'tiene darle la mano el perro okey', 10, 'tiempo_limite', 'free', '2026-01-24 12:00:00', 'fas fa-star', 0, '2026-01-25 00:51:12', 1, 'video', 'otros', NULL, NULL),
(23, 'MANO', 'ENSEÑA A TU PERRO A DAR LA MANO', 5, 'especial', 'free', NULL, 'fas fa-star', 0, '2026-01-30 17:05:12', 1, 'video', 'educacion', 'MIRA EL VIDEO PARA QUE TU PERRO APRENDA A DAR LA MANO SI NO SABE Y GRABA BIEN COMO EN EL VIDEO PARA OBTENER TUS PUNTOS ', NULL),
(24, 'MANO', 'ENSEÑA A TU PERRO A DAR LA MANO', 5, 'especial', 'free', NULL, 'fas fa-star', 0, '2026-01-30 17:29:37', 1, 'video', 'educacion', 'ASDASDAWDADS', NULL),
(25, 'MANO', 'ENSEÑA A MANO', 5, 'especial', 'free', NULL, 'fas fa-star', 0, '2026-01-30 18:09:15', 1, 'video', 'educacion', 'DSAD', NULL),
(26, 'dsada', 'dsadas', 1, 'diaria', 'free', NULL, 'fas fa-star', 0, '2026-01-30 18:54:47', 1, 'video', 'educacion', 'efefefef', 'uploads/task_videos/1769799287_WhatsApp_Video_2026-01-30_at_11.59.56_AM.mp4'),
(27, 'sdasda', 'dsadwdsa', 2, 'especial', 'free', NULL, 'fas fa-star', 0, '2026-01-30 19:11:13', 1, 'video', 'educacion', 'sadawsad', 'uploads/task_videos/1769800273_WhatsApp_Video_2026-01-30_at_11.59.56_AM.mp4'),
(28, 'MANO', 'Enseña a tu mascota a dar la mano', 5, 'especial', 'free', NULL, 'fas fa-star', 0, '2026-01-31 17:28:43', 1, 'video', 'educacion', 'Puedes ayudarte con una comida para poder completar la tarea,\r\nPasa la galleta o el alimento que estes usando por encima de la cabeza para que el perro se siente, despues agarra la mano de tu mascota y dale la comida diciendole que esa es la mano haz esto repetidamente hasta que tu mascota la de sola ', 'uploads/task_videos/1769880523_WhatsApp_Video_2026-01-30_at_11.59.56_AM.mp4'),
(29, 'Call Of dutty', 'SADWADAS', 35, 'diaria', 'free', NULL, 'fas fa-star', 0, '2026-02-24 04:36:40', 0, 'foto', 'ejercicio', '', NULL),
(30, 'Bienvenido', 'Esta tarea es de bienvenida \r\nSolo tienes que publicar una foto de tu mascota a la comunidad \r\n', 5, 'especial', 'free', NULL, 'fas fa-star', 1, '2026-04-01 18:08:56', 1, 'foto', 'comunidad', 'Ve a la parte de comunidad y sube una foto y enlazala con tu mascota \r\ntoma captura y envianos la evidencia para que obtengas tus primeros puntos', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tratamientos`
--

DROP TABLE IF EXISTS `tratamientos`;
CREATE TABLE IF NOT EXISTS `tratamientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `consulta_id` int NOT NULL,
  `tratamiento` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `medicamento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_trat_consulta` (`consulta_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_activity_log`
--

DROP TABLE IF EXISTS `user_activity_log`;
CREATE TABLE IF NOT EXISTS `user_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `activity_date` date NOT NULL,
  `activity_type` varchar(50) DEFAULT 'login' COMMENT 'login, task_completed, plan_generated, etc',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`activity_date`),
  KEY `idx_user_date` (`user_id`,`activity_date`)
) ENGINE=InnoDB AUTO_INCREMENT=647 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Track daily user activity for health score calculation';

--
-- Volcado de datos para la tabla `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `activity_date`, `activity_type`, `created_at`) VALUES
(623, 48, '2026-04-01', 'dashboard_visit', '2026-04-01 18:05:02'),
(639, 48, '2026-04-02', 'dashboard_visit', '2026-04-02 05:36:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `newsletter` tinyint(1) DEFAULT '0',
  `rol` varchar(20) DEFAULT 'usuario',
  `premium` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `puntos` int DEFAULT '0',
  `nivel` enum('bronce','plata','oro','platino') DEFAULT 'bronce',
  `total_puntos_ganados` int DEFAULT '0',
  `citas_mes_actual` int DEFAULT '0',
  `ultimo_reset_citas` date DEFAULT NULL,
  `nivel_numerico` int DEFAULT '1',
  `experiencia_nivel` int DEFAULT '0',
  `racha_dias` int DEFAULT '0',
  `ultima_tarea_fecha` date DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `telefono`, `ciudad`, `foto_perfil`, `newsletter`, `rol`, `premium`, `created_at`, `updated_at`, `puntos`, `nivel`, `total_puntos_ganados`, `citas_mes_actual`, `ultimo_reset_citas`, `nivel_numerico`, `experiencia_nivel`, `racha_dias`, `ultima_tarea_fecha`, `remember_token`, `ultimo_login`) VALUES
(1, 'Administrador', 'admin@rugal.com', '$2y$10$I5qAB6Q8BaWcGyhma0JJ4OtPWID.KHenSxz1ryg1NG2TuFr.u5E8W', '555-0000', 'Cali', NULL, 0, 'admin', 0, '2025-12-13 06:42:53', '2026-04-02 05:54:36', 0, 'bronce', 0, 0, NULL, 1, 0, 0, NULL, NULL, '2026-04-02 00:54:36'),
(47, 'Dennis Santiago', 'a4ntiag0@gmail.com', '$2y$10$EbUGz84GZRwjuoekV2twYO0LupaUkZN0Juf8ka.cC2ZtrD0ZXrEyC', 'a4ntiag0@gmail.com', 'Cali', NULL, 0, 'veterinaria', 0, '2026-04-01 16:16:06', '2026-04-02 20:16:10', 0, 'bronce', 0, 0, NULL, 1, 0, 0, NULL, NULL, '2026-04-02 15:16:10'),
(48, 'Dennis Santiago', 'a4ntiago@gmail.com', '$2y$10$uMgXo.1i7iWXgHS6meUG3OFciCN/w5lZGXKdzKN1KgnRYAGzKaIfS', '3167197604', 'Cali', NULL, 0, 'usuario', 0, '2026-04-01 18:04:59', '2026-04-02 19:06:30', 5, 'bronce', 5, 0, NULL, 1, 1, 1, '2026-04-02', NULL, '2026-04-02 14:06:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_tienda`
--

DROP TABLE IF EXISTS `ventas_tienda`;
CREATE TABLE IF NOT EXISTS `ventas_tienda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tienda_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` varchar(50) DEFAULT 'efectivo',
  `estado` varchar(20) DEFAULT 'completada',
  PRIMARY KEY (`id`),
  KEY `tienda_id` (`tienda_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `aliados`
--
ALTER TABLE `aliados`
  ADD CONSTRAINT `aliados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `bloqueos_horario`
--
ALTER TABLE `bloqueos_horario`
  ADD CONSTRAINT `bloqueos_horario_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `aliados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `canjes`
--
ALTER TABLE `canjes`
  ADD CONSTRAINT `canjes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `canjes_ibfk_2` FOREIGN KEY (`recompensa_id`) REFERENCES `recompensas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`veterinaria_id`) REFERENCES `aliados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_citas_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios_veterinaria` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comentarios_educacion`
--
ALTER TABLE `comentarios_educacion`
  ADD CONSTRAINT `comentarios_educacion_ibfk_1` FOREIGN KEY (`contenido_id`) REFERENCES `contenido_educativo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_educacion_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_ventas_tienda`
--
ALTER TABLE `detalle_ventas_tienda`
  ADD CONSTRAINT `detalle_ventas_tienda_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas_tienda` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_ventas_tienda_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos_tienda` (`id`);

--
-- Filtros para la tabla `disponibilidad_veterinaria`
--
ALTER TABLE `disponibilidad_veterinaria`
  ADD CONSTRAINT `disponibilidad_veterinaria_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `aliados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estado_animo`
--
ALTER TABLE `estado_animo`
  ADD CONSTRAINT `estado_animo_ibfk_1` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD CONSTRAINT `mascotas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mascotas_salud`
--
ALTER TABLE `mascotas_salud`
  ADD CONSTRAINT `fk_mascotas_salud_mascota` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`);

--
-- Filtros para la tabla `peso_historial`
--
ALTER TABLE `peso_historial`
  ADD CONSTRAINT `peso_historial_ibfk_1` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `planes_salud`
--
ALTER TABLE `planes_salud`
  ADD CONSTRAINT `planes_salud_ibfk_1` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planes_salud_ibfk_2` FOREIGN KEY (`veterinaria_id`) REFERENCES `aliados` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `planes_salud_general`
--
ALTER TABLE `planes_salud_general`
  ADD CONSTRAINT `planes_salud_general_ibfk_1` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planes_salud_general_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `planes_salud_mensual`
--
ALTER TABLE `planes_salud_mensual`
  ADD CONSTRAINT `planes_salud_mensual_ibfk_1` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planes_salud_mensual_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `plan_salud_mensual_tareas`
--
ALTER TABLE `plan_salud_mensual_tareas`
  ADD CONSTRAINT `plan_salud_mensual_tareas_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `planes_salud_mensual` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos_tienda`
--
ALTER TABLE `productos_tienda`
  ADD CONSTRAINT `productos_tienda_ibfk_1` FOREIGN KEY (`tienda_id`) REFERENCES `aliados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos_veterinaria`
--
ALTER TABLE `productos_veterinaria`
  ADD CONSTRAINT `productos_veterinaria_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `aliados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD CONSTRAINT `promociones_ibfk_1` FOREIGN KEY (`aliado_id`) REFERENCES `aliados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD CONSTRAINT `publicaciones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `publicaciones_ibfk_2` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `puntos_historial`
--
ALTER TABLE `puntos_historial`
  ADD CONSTRAINT `puntos_historial_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recompensas`
--
ALTER TABLE `recompensas`
  ADD CONSTRAINT `recompensas_ibfk_1` FOREIGN KEY (`aliado_id`) REFERENCES `aliados` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `recordatorios`
--
ALTER TABLE `recordatorios`
  ADD CONSTRAINT `fk_recordatorios_mascota` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recordatorios_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recordatorios_plan`
--
ALTER TABLE `recordatorios_plan`
  ADD CONSTRAINT `recordatorios_plan_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `planes_salud` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `servicios_veterinaria`
--
ALTER TABLE `servicios_veterinaria`
  ADD CONSTRAINT `servicios_veterinaria_ibfk_1` FOREIGN KEY (`veterinaria_id`) REFERENCES `aliados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD CONSTRAINT `suscripciones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `suscripciones_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `planes_premium` (`id`);

--
-- Filtros para la tabla `tareas_completadas`
--
ALTER TABLE `tareas_completadas`
  ADD CONSTRAINT `tareas_completadas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tareas_completadas_ibfk_2` FOREIGN KEY (`tarea_id`) REFERENCES `tareas_comunidad` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ventas_tienda`
--
ALTER TABLE `ventas_tienda`
  ADD CONSTRAINT `ventas_tienda_ibfk_1` FOREIGN KEY (`tienda_id`) REFERENCES `aliados` (`id`),
  ADD CONSTRAINT `ventas_tienda_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
