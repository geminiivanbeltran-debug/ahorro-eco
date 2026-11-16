-- Script de estructura de base de datos para Ahorro Eco
-- Sin registros personales (listo para despliegue limpio en MySQL / MariaDB)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Creación de la base de datos `ahorvonr_1`
--
CREATE DATABASE IF NOT EXISTS `ahorvonr_1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ahorvonr_1`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `valor_kwh` decimal(10,2) NOT NULL DEFAULT 850.00,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dispositivos_iot`
--
CREATE TABLE IF NOT EXISTS `dispositivos_iot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre_dispositivo` varchar(100) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'Conectado',
  `consumo_actual` decimal(10,4) DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `dispositivos_iot_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dispositivos_usuario`
--
CREATE TABLE IF NOT EXISTS `dispositivos_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nombre_dispositivo` varchar(100) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `tarifa_kwh` decimal(10,2) DEFAULT 850.00,
  `potencia_watts` decimal(10,2) DEFAULT 120.00,
  `switch_code` varchar(20) DEFAULT 'switch_1',
  PRIMARY KEY (`id`),
  KEY `idx_usuario_disp` (`usuario_id`),
  CONSTRAINT `dispositivos_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_consumo_iot`
--
CREATE TABLE IF NOT EXISTS `historial_consumo_iot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `nombre_dispositivo` varchar(100) NOT NULL,
  `switch_code` varchar(20) NOT NULL,
  `estado` varchar(20) NOT NULL,
  `potencia_watts` decimal(10,2) NOT NULL DEFAULT 0.00,
  `consumo_kwh` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `costo_cop` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_lectura` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_fecha` (`usuario_id`,`fecha_lectura`),
  CONSTRAINT `historial_consumo_iot_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
