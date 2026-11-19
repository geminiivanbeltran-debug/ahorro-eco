-- ============================================================
-- Ahorro Eco - Migración al prototipo (TRL5)
-- Ejecutar UNA sola vez sobre la base de datos ya existente
-- (la de la demo con Tuya). Compatible con MySQL 5.7+/8 y MariaDB.
-- Las tablas de la demo (dispositivos_iot, dispositivos_usuario,
-- historial_consumo_iot) NO se tocan: el módulo IOT sigue funcionando.
-- ============================================================

SET NAMES utf8mb4;

-- 1. Perfil del usuario (RF-01): hogar o microempresa, estrato o sector,
--    y límite diario de consumo para la alerta de la regla R12.
ALTER TABLE `usuarios`
  ADD COLUMN `tipo_usuario` ENUM('hogar','microempresa') NOT NULL DEFAULT 'hogar' AFTER `correo`,
  ADD COLUMN `estrato` TINYINT NULL DEFAULT NULL AFTER `tipo_usuario`,
  ADD COLUMN `sector` VARCHAR(40) NULL DEFAULT NULL AFTER `estrato`,
  ADD COLUMN `limite_diario_kwh` DECIMAL(8,2) NULL DEFAULT NULL AFTER `sector`;

-- La dirección completa ya no es obligatoria: el documento declara que solo
-- se recogen los datos necesarios para el funcionamiento (Ley 1581 de 2012).
ALTER TABLE `usuarios`
  MODIFY COLUMN `direccion` VARCHAR(150) NULL DEFAULT NULL;

-- 2. Equipos del usuario (RF-02): potencia, cantidad y horas de uso.
CREATE TABLE IF NOT EXISTS `equipos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `nombre` VARCHAR(80) NOT NULL,
  `categoria` VARCHAR(20) NOT NULL,
  `potencia_w` DECIMAL(8,2) NOT NULL,          -- potencia por unidad
  `cantidad` SMALLINT NOT NULL DEFAULT 1,
  `horas_dia` DECIMAL(4,1) NOT NULL,           -- horas de uso al día
  `franja` ENUM('madrugada','manana','tarde','noche') NOT NULL DEFAULT 'noche',
  `deja_reposo` TINYINT(1) NOT NULL DEFAULT 0, -- queda conectado sin usarse (standby)
  `creado` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_equipos_usuario` (`usuario_id`),
  CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Lecturas generadas por el simulador (RF-03).
CREATE TABLE IF NOT EXISTS `lecturas` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `equipo_id` INT(11) NOT NULL,
  `fecha_lectura` DATETIME NOT NULL,
  `intervalo_min` SMALLINT NOT NULL,
  `potencia_w` DECIMAL(10,2) NOT NULL,
  `consumo_kwh` DECIMAL(10,5) NOT NULL,
  `costo_cop` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lecturas_usuario_fecha` (`usuario_id`,`fecha_lectura`),
  KEY `idx_lecturas_equipo` (`equipo_id`),
  CONSTRAINT `lecturas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lecturas_ibfk_2` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Recomendaciones generadas por el motor de reglas y su calificación (RF-05, RF-06).
--    La combinación (usuario, regla, equipo) es única: así la calificación
--    se conserva cuando el motor vuelve a evaluar. equipo_clave = 0 si la
--    regla no es de un equipo concreto.
CREATE TABLE IF NOT EXISTS `recomendaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `regla_id` VARCHAR(8) NOT NULL,
  `equipo_clave` INT(11) NOT NULL DEFAULT 0,
  `titulo` VARCHAR(160) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `explicacion` TEXT NOT NULL,
  `franja` VARCHAR(20) NULL DEFAULT NULL,
  `ahorro_kwh_mes` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `ahorro_cop_mes` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `tipo_beneficio` VARCHAR(12) NOT NULL DEFAULT 'ahorro',
  `esfuerzo` TINYINT NOT NULL DEFAULT 1,
  `vigente` TINYINT(1) NOT NULL DEFAULT 1,
  `calificacion` TINYINT NULL DEFAULT NULL,
  `fecha_generada` DATETIME NOT NULL,
  `fecha_calificacion` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recomendacion` (`usuario_id`,`regla_id`,`equipo_clave`),
  CONSTRAINT `recomendaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
