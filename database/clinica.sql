-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-10-2026 a las 06:32:49
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `clinica`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cita`
--

CREATE TABLE `cita` (
  `id_cita` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'pendiente',
  `id_paciente` int(11) NOT NULL,
  `id_profesional` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL,
  `id_estudio` int(11) NOT NULL,
  `id_hc` int(11) DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `cita`
--

INSERT INTO `cita` (`id_cita`, `fecha`, `hora_inicio`, `hora_fin`, `estado`, `id_paciente`, `id_profesional`, `id_sucursal`, `id_estudio`, `id_hc`) VALUES
(7, '2026-10-30', '16:30:00', '17:00:00', 'cancelada', 7, 4, 5, 3, NULL),
(8, '2026-10-31', '11:30:00', '12:00:00', 'cancelada', 7, 4, 5, 3, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipamiento_sucursal`
--

CREATE TABLE `equipamiento_sucursal` (
  `id_eq_su` int(11) NOT NULL,
  `id_equipamiento` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipamiento_total`
--

CREATE TABLE `equipamiento_total` (
  `id_eq_t` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudio`
--

CREATE TABLE `estudio` (
  `id_estudio` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_de_estudio` varchar(50) DEFAULT NULL,
  `es_por_referencia` tinyint(1) DEFAULT 0,
  `duracion` varchar(11) DEFAULT NULL,
  `preparacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudio`
--

INSERT INTO `estudio` (`id_estudio`, `nombre`, `tipo_de_estudio`, `es_por_referencia`, `duracion`, `preparacion`) VALUES
(1, 'Radiografía perical', 'Rayos X', 0, '25 Minutos', 'Sin preparación especial'),
(2, 'Molde Para Impresion 3D', 'Impresion', 0, '10 Minutos', NULL),
(3, 'Cita Quirurgica Para Implantes', 'Cirguia', 0, '30 Minutos ', 'Radiografías Panorámicas, Estudio bucal'),
(4, 'Estudio Bucal', 'Revision', 0, '20 Minutos', NULL),
(5, 'Radiografías Panorámicas', 'Rayos X', 0, '15 Minutos', NULL),
(6, 'Tomografías Para Cirugía', 'Rayos X', 0, '15 Minutos', NULL),
(7, 'Fotografías Intrabucales y Extrabucales', 'Estudio', 0, '10 Minutos', NULL),
(8, 'Tomografías Para Implantes', 'Estudio Por Imagenes En 3D', 0, '40 Segundos', NULL),
(9, 'Escáner de Modelos Dentales', 'Molde Dental', 0, '5 minutos', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_clinico`
--

CREATE TABLE `historial_clinico` (
  `id_hc` int(11) NOT NULL,
  `tratamiento` text DEFAULT NULL,
  `nota` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_clinico`
--

INSERT INTO `historial_clinico` (`id_hc`, `tratamiento`, `nota`) VALUES
(1, 'Ninguno', 'Primera consulta');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_profesional`
--

CREATE TABLE `horario_profesional` (
  `id_horario` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL,
  `dia` tinyint(4) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagen`
--

CREATE TABLE `imagen` (
  `id_imagen` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `id_resultado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden`
--

CREATE TABLE `orden` (
  `id_orden` int(11) NOT NULL,
  `tipo_de_orden` varchar(50) NOT NULL,
  `fecha` date NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_profesional` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paciente`
--

CREATE TABLE `paciente` (
  `id_paciente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `paciente`
--

INSERT INTO `paciente` (`id_paciente`) VALUES
(7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `rol` varchar(30) NOT NULL,
  `modulo` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`rol`, `modulo`) VALUES
('profesional', 'citas'),
('profesional', 'pacientes'),
('profesional', 'resultados'),
('recepcionista', 'citas'),
('recepcionista', 'pacientes'),
('tecnico', 'pacientes'),
('tecnico', 'resultados');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_sucursal`
--

CREATE TABLE `personal_sucursal` (
  `id_usuario` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personal_sucursal`
--

INSERT INTO `personal_sucursal` (`id_usuario`, `id_sucursal`) VALUES
(4, 1),
(4, 2),
(4, 4),
(8, 3),
(8, 5),
(8, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesional`
--

CREATE TABLE `profesional` (
  `id_profesional` int(11) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `tipo_de_profesional` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesional`
--

INSERT INTO `profesional` (`id_profesional`, `telefono`, `tipo_de_profesional`) VALUES
(3, '0923428', 'admin'),
(4, '09234890', 'implantólogo'),
(8, NULL, 'Cirujana maxilofacial'),
(9, NULL, 'Periodoncista');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados`
--

CREATE TABLE `resultados` (
  `id_resultado` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `id_estudio` int(11) NOT NULL,
  `id_cita` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `id_sucursal` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `localidad` varchar(50) DEFAULT NULL,
  `calle` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`id_sucursal`, `nombre`, `departamento`, `localidad`, `calle`) VALUES
(1, 'Sucursal Nuevo Centro', 'Montevideo', 'Centro', 'Bv. Gral. Artigas 3126, 11600 Montevideo, Departamento de Montevideo'),
(2, 'Sucursal Montevideo Shopping', 'Montevideo', 'Departamento De Montevideo', 'Av. Luis Alberto de Herrera 1248, 11300 Montevideo, Departamento de Montevideo'),
(3, 'Clínica Imagen Lagomar', 'canelones', 'ciudad de la costa', 'Lagomar Norte, Av. Giannattasio 236, 15800 Ciudad de la Costa, Departamento de Canelones'),
(4, 'Clinica Imagen Portal De Las Americas', 'canelones', 'Departamento De Canelones', 'Portal de las Américas, Av. De las Américas 6000, loc 109, Departamento de Canelones'),
(5, 'Clinica Imagen Atlantida', 'Canelones', 'Atlantida', 'C. 22 esquina, 15200 Atlántida, Departamento de Canelones'),
(6, 'Clinica Imagen De La Costa', 'Canelones', 'Ciudad De La Costa', 'Av. Giannattasio km. 22, 15001 Ciudad de la Costa, Departamento de Canelones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal_estudio`
--

CREATE TABLE `sucursal_estudio` (
  `id_sucursal` int(11) NOT NULL,
  `id_estudio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sucursal_estudio`
--

INSERT INTO `sucursal_estudio` (`id_sucursal`, `id_estudio`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(3, 7),
(3, 8),
(3, 9),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6),
(4, 7),
(4, 8),
(4, 9),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(5, 6),
(5, 7),
(5, 8),
(5, 9),
(6, 1),
(6, 2),
(6, 3),
(6, 4),
(6, 5),
(6, 6),
(6, 7),
(6, 8),
(6, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` varchar(30) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_completo`, `email`, `cedula`, `contrasena`, `rol`, `fecha_nacimiento`, `telefono`, `activo`) VALUES
(3, 'tomas', 'tomasbourdetti@gmail.com', '55920817', '$2y$10$ft9FpDzaJdc66Pi6KHKaJOx8jpI1HVRCL5I6xTXhGdKz9lW1cttGu', 'admin', '2006-06-06', '0923428', 1),
(4, 'Pedro Gomez', 'pedro@gmail.com', '34783473', 'Pedro123', 'profesional', '1999-06-06', NULL, 1),
(7, 'sebastian borges', 'sebastianborges@gmail.com', '44901234', '$2y$10$uzE/CrUQ1qPQsqpXlv056.ClRfiVg5Fms0T0y.1MjDxyIf1Ck022O', 'paciente', '2027-01-28', '097654726', 1),
(8, 'Lara Juana', 'lara@gmail.com', '92378264', '$2y$10$0HKrKieOMQJ/hMcJBWF6ruCMr4gGsy2lRrfcYzhTaU4urXN0VKJ1a', 'profesional', NULL, NULL, 1),
(9, 'Ernesto Gomez', 'ernesto', '12345678', 'ernesto123', 'profesional', NULL, NULL, 1);

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `usuarios_validar_ins` BEFORE INSERT ON `usuarios` FOR EACH ROW BEGIN
  IF NEW.cedula NOT REGEXP '^[0-9]{8}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cédula debe tener exactamente 8 números';
  END IF;
  IF NEW.telefono IS NOT NULL AND NEW.telefono <> '' AND NEW.telefono NOT REGEXP '^[0-9]{9}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El teléfono debe tener exactamente 9 números';
  END IF;
  IF NEW.fecha_nacimiento IS NOT NULL AND (NEW.fecha_nacimiento > CURDATE() OR NEW.fecha_nacimiento < DATE_SUB(CURDATE(), INTERVAL 120 YEAR)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fecha de nacimiento no válida (futura o de hace más de 120 años)';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `usuarios_validar_upd` BEFORE UPDATE ON `usuarios` FOR EACH ROW BEGIN
  IF NOT (NEW.cedula <=> OLD.cedula) AND NEW.cedula NOT REGEXP '^[0-9]{8}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cédula debe tener exactamente 8 números';
  END IF;
  IF NOT (NEW.telefono <=> OLD.telefono) AND NEW.telefono IS NOT NULL AND NEW.telefono <> '' AND NEW.telefono NOT REGEXP '^[0-9]{9}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El teléfono debe tener exactamente 9 números';
  END IF;
  IF NOT (NEW.fecha_nacimiento <=> OLD.fecha_nacimiento) AND NEW.fecha_nacimiento IS NOT NULL
     AND (NEW.fecha_nacimiento > CURDATE() OR NEW.fecha_nacimiento < DATE_SUB(CURDATE(), INTERVAL 120 YEAR)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fecha de nacimiento no válida (futura o de hace más de 120 años)';
  END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cita`
--
ALTER TABLE `cita`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `id_sucursal` (`id_sucursal`),
  ADD KEY `id_estudio` (`id_estudio`),
  ADD KEY `id_hc` (`id_hc`),
  ADD KEY `idx_cita_fecha` (`fecha`),
  ADD KEY `idx_cita_paciente` (`id_paciente`),
  ADD KEY `idx_cita_profesional` (`id_profesional`);

--
-- Indices de la tabla `equipamiento_sucursal`
--
ALTER TABLE `equipamiento_sucursal`
  ADD PRIMARY KEY (`id_eq_su`),
  ADD KEY `id_equipamiento` (`id_equipamiento`),
  ADD KEY `id_sucursal` (`id_sucursal`);

--
-- Indices de la tabla `equipamiento_total`
--
ALTER TABLE `equipamiento_total`
  ADD PRIMARY KEY (`id_eq_t`);

--
-- Indices de la tabla `estudio`
--
ALTER TABLE `estudio`
  ADD PRIMARY KEY (`id_estudio`);

--
-- Indices de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD PRIMARY KEY (`id_hc`);

--
-- Indices de la tabla `horario_profesional`
--
ALTER TABLE `horario_profesional`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_sucursal` (`id_sucursal`);

--
-- Indices de la tabla `imagen`
--
ALTER TABLE `imagen`
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `id_resultado` (`id_resultado`);

--
-- Indices de la tabla `orden`
--
ALTER TABLE `orden`
  ADD PRIMARY KEY (`id_orden`),
  ADD KEY `id_profesional` (`id_profesional`),
  ADD KEY `idx_orden_paciente` (`id_paciente`);

--
-- Indices de la tabla `paciente`
--
ALTER TABLE `paciente`
  ADD PRIMARY KEY (`id_paciente`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`rol`,`modulo`);

--
-- Indices de la tabla `personal_sucursal`
--
ALTER TABLE `personal_sucursal`
  ADD PRIMARY KEY (`id_usuario`,`id_sucursal`),
  ADD KEY `id_sucursal` (`id_sucursal`);

--
-- Indices de la tabla `profesional`
--
ALTER TABLE `profesional`
  ADD PRIMARY KEY (`id_profesional`);

--
-- Indices de la tabla `resultados`
--
ALTER TABLE `resultados`
  ADD PRIMARY KEY (`id_resultado`),
  ADD KEY `id_estudio` (`id_estudio`),
  ADD KEY `id_cita` (`id_cita`);

--
-- Indices de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD PRIMARY KEY (`id_sucursal`);

--
-- Indices de la tabla `sucursal_estudio`
--
ALTER TABLE `sucursal_estudio`
  ADD PRIMARY KEY (`id_sucursal`,`id_estudio`),
  ADD KEY `id_estudio` (`id_estudio`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cita`
--
ALTER TABLE `cita`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipamiento_sucursal`
--
ALTER TABLE `equipamiento_sucursal`
  MODIFY `id_eq_su` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipamiento_total`
--
ALTER TABLE `equipamiento_total`
  MODIFY `id_eq_t` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudio`
--
ALTER TABLE `estudio`
  MODIFY `id_estudio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  MODIFY `id_hc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `horario_profesional`
--
ALTER TABLE `horario_profesional`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `imagen`
--
ALTER TABLE `imagen`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden`
--
ALTER TABLE `orden`
  MODIFY `id_orden` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resultados`
--
ALTER TABLE `resultados`
  MODIFY `id_resultado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `id_sucursal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cita`
--
ALTER TABLE `cita`
  ADD CONSTRAINT `cita_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `paciente` (`id_paciente`),
  ADD CONSTRAINT `cita_ibfk_2` FOREIGN KEY (`id_profesional`) REFERENCES `profesional` (`id_profesional`),
  ADD CONSTRAINT `cita_ibfk_3` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`),
  ADD CONSTRAINT `cita_ibfk_4` FOREIGN KEY (`id_estudio`) REFERENCES `estudio` (`id_estudio`),
  ADD CONSTRAINT `cita_ibfk_5` FOREIGN KEY (`id_hc`) REFERENCES `historial_clinico` (`id_hc`);

--
-- Filtros para la tabla `equipamiento_sucursal`
--
ALTER TABLE `equipamiento_sucursal`
  ADD CONSTRAINT `equipamiento_sucursal_ibfk_1` FOREIGN KEY (`id_equipamiento`) REFERENCES `equipamiento_total` (`id_eq_t`),
  ADD CONSTRAINT `equipamiento_sucursal_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`);

--
-- Filtros para la tabla `horario_profesional`
--
ALTER TABLE `horario_profesional`
  ADD CONSTRAINT `horario_profesional_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `horario_profesional_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `imagen`
--
ALTER TABLE `imagen`
  ADD CONSTRAINT `imagen_ibfk_1` FOREIGN KEY (`id_resultado`) REFERENCES `resultados` (`id_resultado`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orden`
--
ALTER TABLE `orden`
  ADD CONSTRAINT `orden_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `paciente` (`id_paciente`),
  ADD CONSTRAINT `orden_ibfk_2` FOREIGN KEY (`id_profesional`) REFERENCES `profesional` (`id_profesional`);

--
-- Filtros para la tabla `paciente`
--
ALTER TABLE `paciente`
  ADD CONSTRAINT `paciente_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal_sucursal`
--
ALTER TABLE `personal_sucursal`
  ADD CONSTRAINT `personal_sucursal_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `personal_sucursal_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesional`
--
ALTER TABLE `profesional`
  ADD CONSTRAINT `profesional_ibfk_1` FOREIGN KEY (`id_profesional`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `resultados`
--
ALTER TABLE `resultados`
  ADD CONSTRAINT `resultados_ibfk_1` FOREIGN KEY (`id_estudio`) REFERENCES `estudio` (`id_estudio`),
  ADD CONSTRAINT `resultados_ibfk_2` FOREIGN KEY (`id_cita`) REFERENCES `cita` (`id_cita`) ON DELETE SET NULL;

--
-- Filtros para la tabla `sucursal_estudio`
--
ALTER TABLE `sucursal_estudio`
  ADD CONSTRAINT `sucursal_estudio_ibfk_1` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursal` (`id_sucursal`) ON DELETE CASCADE,
  ADD CONSTRAINT `sucursal_estudio_ibfk_2` FOREIGN KEY (`id_estudio`) REFERENCES `estudio` (`id_estudio`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
