-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 16-11-2026 a las 13:48:34
-- Versión del servidor: 11.4.13-MariaDB-cll-lve
-- Versión de PHP: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ahorvonr_1`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dispositivos_iot`
--

CREATE TABLE `dispositivos_iot` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre_dispositivo` varchar(100) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'Conectado',
  `consumo_actual` decimal(10,4) DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `dispositivos_iot`
--

INSERT INTO `dispositivos_iot` (`id`, `usuario_id`, `nombre_dispositivo`, `estado`, `consumo_actual`) VALUES
(4, 5, 'Toma TV', 'Encendido', 0.1200),
(5, 5, 'Toma MÃºltiple - Canal 1', 'Encendido', 0.0600),
(6, 5, 'Toma MÃºltiple - Canal 2', 'Encendido', 0.0600);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dispositivos_usuario`
--

CREATE TABLE `dispositivos_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_dispositivo` varchar(100) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `tarifa_kwh` decimal(10,2) DEFAULT 800.00,
  `potencia_watts` decimal(10,2) DEFAULT 120.00,
  `switch_code` varchar(20) DEFAULT 'switch_1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_consumo_iot`
--

CREATE TABLE `historial_consumo_iot` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `nombre_dispositivo` varchar(100) NOT NULL,
  `switch_code` varchar(20) NOT NULL,
  `estado` varchar(20) NOT NULL,
  `potencia_watts` decimal(10,2) NOT NULL DEFAULT 0.00,
  `consumo_kwh` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `costo_cop` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_lectura` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_consumo_iot`
--

INSERT INTO `historial_consumo_iot` (`id`, `usuario_id`, `device_id`, `nombre_dispositivo`, `switch_code`, `estado`, `potencia_watts`, `consumo_kwh`, `costo_cop`, `fecha_lectura`) VALUES
(1, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 6.00, '2026-11-16 17:26:41'),
(2, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0130, 0.65, '2026-11-16 17:26:41'),
(3, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0130, 0.65, '2026-11-16 17:26:41'),
(4, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 17:26:58'),
(5, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0130, 0.65, '2026-11-16 17:26:58'),
(6, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0130, 0.65, '2026-11-16 17:26:58'),
(7, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 6.00, '2026-11-16 17:27:02'),
(8, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0130, 0.65, '2026-11-16 17:27:02'),
(9, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0130, 0.65, '2026-11-16 17:27:02'),
(10, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 6.00, '2026-11-16 17:31:51'),
(11, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0125, 0.63, '2026-11-16 17:31:51'),
(12, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0125, 0.63, '2026-11-16 17:31:51'),
(13, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 17:38:23'),
(14, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:38:23'),
(15, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:38:23'),
(16, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 17:39:03'),
(17, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:39:03'),
(18, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:39:03'),
(19, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 17:39:23'),
(20, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:39:23'),
(21, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:39:23'),
(22, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 17:40:17'),
(23, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:40:17'),
(24, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:40:17'),
(25, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 17:40:36'),
(26, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:40:36'),
(27, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 17:40:36'),
(28, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 18:00:07'),
(29, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:00:07'),
(30, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:00:07'),
(31, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 18:18:36'),
(32, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:18:36'),
(33, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:18:36'),
(34, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 18:37:23'),
(35, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:37:23'),
(36, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:37:23'),
(37, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:28'),
(38, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:37:28'),
(39, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 28.60, 0.0286, 24.31, '2026-11-16 18:37:28'),
(40, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:32'),
(41, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:32'),
(42, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 57.20, 0.0572, 48.62, '2026-11-16 18:37:32'),
(43, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:36'),
(44, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:36'),
(45, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:36'),
(46, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:41'),
(47, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:41'),
(48, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 60.00, 0.0600, 51.00, '2026-11-16 18:37:41'),
(49, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Apagado', 0.00, 0.0000, 0.00, '2026-11-16 18:37:45'),
(50, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 60.00, 0.0600, 51.00, '2026-11-16 18:37:45'),
(51, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 60.00, 0.0600, 51.00, '2026-11-16 18:37:45'),
(52, 5, 'eba43172697800f9e1pgri', 'Toma TV', 'switch_1', 'Encendido', 120.00, 0.1200, 102.00, '2026-11-16 18:37:48'),
(53, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 1', 'switch_1', 'Encendido', 60.00, 0.0600, 51.00, '2026-11-16 18:37:48'),
(54, 5, 'ebb9ce311b9f23189eli7h', 'Toma MÃºltiple - Canal 2', 'switch_2', 'Encendido', 60.00, 0.0600, 51.00, '2026-11-16 18:37:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `valor_kwh` decimal(10,2) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `departamento`, `ciudad`, `direccion`, `valor_kwh`, `correo`, `clave`, `fecha_registro`) VALUES
(5, 'IvÃ¡n', 'Cuenca', 'Cundinamarca', 'BogotÃ¡ D.C.', '760013316', 850.00, 'ivandariobeltran@gmail.com', '$2y$10$M4xWXDgUoqwNSqQXCtshuuwYA289CfB8P1Ipt3eG63.1CWdQBUFsu', '2026-08-30 00:11:00'),
(6, 'Rocio', 'Aldana Fernandez', 'Valle del Cauca', 'Cali', 'Carrera 30a 30a-108', 120000.00, 'rocioaldanafernandez4@gmail.com', '$2y$10$gfkSnt47.faNhIauuKK3guMt.xmo5qz65r7Sw.CgzHo6ayrLhQ9.C', '2026-08-30 00:20:53'),
(7, 'Petunia', 'Sinfuegos', 'Valle del Cauca', 'Cali', 'Cra 7e Bis #67-144', 300.00, 'kavagoor2018@outlook.com', '$2y$10$rXA/5sPO3cjxHY4kZlZXr.MU5eem.WFliJCC36QjDnaSvp6haq8tu', '2026-09-02 21:03:18');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `dispositivos_iot`
--
ALTER TABLE `dispositivos_iot`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `dispositivos_usuario`
--
ALTER TABLE `dispositivos_usuario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_consumo_iot`
--
ALTER TABLE `historial_consumo_iot`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_fecha` (`usuario_id`,`fecha_lectura`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `dispositivos_iot`
--
ALTER TABLE `dispositivos_iot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `dispositivos_usuario`
--
ALTER TABLE `dispositivos_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_consumo_iot`
--
ALTER TABLE `historial_consumo_iot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `dispositivos_iot`
--
ALTER TABLE `dispositivos_iot`
  ADD CONSTRAINT `dispositivos_iot_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
