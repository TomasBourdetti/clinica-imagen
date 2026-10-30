-- =====================================================
-- Base de datos: Clínica de estudios / imágenes
-- Generada a partir del modelo entidad-relación (MER)
-- Motor: MySQL 8 / MariaDB (adaptable a PostgreSQL)
-- =====================================================

DROP DATABASE IF EXISTS clinica;
CREATE DATABASE clinica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinica;

-- -----------------------------------------------------
-- USUARIOS (supertipo de la especialización)
-- -----------------------------------------------------
CREATE TABLE usuarios (
    id_usuario       INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo  VARCHAR(100) NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    cedula           VARCHAR(20)  NOT NULL UNIQUE,
    contrasena       VARCHAR(255) NOT NULL,          -- guardar HASH, nunca texto plano
    rol              VARCHAR(30)  NOT NULL,
    fecha_nacimiento DATE,
    telefono         VARCHAR(20)
);

-- -----------------------------------------------------
-- SUBTIPOS: PACIENTE y PROFESIONAL
-- (comparten la clave con usuarios)
-- -----------------------------------------------------
CREATE TABLE paciente (
    id_paciente INT PRIMARY KEY,
    FOREIGN KEY (id_paciente) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
);

CREATE TABLE profesional (
    id_profesional     INT PRIMARY KEY,
    telefono           VARCHAR(20),
    tipo_de_profesional VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_profesional) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
);

-- -----------------------------------------------------
-- SUCURSAL
-- -----------------------------------------------------
CREATE TABLE sucursal (
    id_sucursal  INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    departamento VARCHAR(50),   -- atributo compuesto "direccion"
    localidad    VARCHAR(50),
    calle        VARCHAR(100)
);

-- -----------------------------------------------------
-- EQUIPAMIENTO
-- -----------------------------------------------------
CREATE TABLE equipamiento_total (
    id_eq_t   INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    marca     VARCHAR(50),
    modelo    VARCHAR(50),
    categoria VARCHAR(50),
    estado    VARCHAR(30)
);

CREATE TABLE equipamiento_sucursal (
    id_eq_su       INT AUTO_INCREMENT PRIMARY KEY,
    id_equipamiento INT NOT NULL,
    id_sucursal    INT NOT NULL,
    FOREIGN KEY (id_equipamiento) REFERENCES equipamiento_total(id_eq_t),
    FOREIGN KEY (id_sucursal)     REFERENCES sucursal(id_sucursal)
);

-- -----------------------------------------------------
-- ORDEN (paciente 1:N orden, profesional 1:N orden)
-- -----------------------------------------------------
CREATE TABLE orden (
    id_orden       INT AUTO_INCREMENT PRIMARY KEY,
    tipo_de_orden  VARCHAR(50) NOT NULL,
    fecha          DATE NOT NULL,
    id_paciente    INT NOT NULL,   -- "recibe"
    id_profesional INT NOT NULL,   -- "atiende"
    FOREIGN KEY (id_paciente)    REFERENCES paciente(id_paciente),
    FOREIGN KEY (id_profesional) REFERENCES profesional(id_profesional)
);

-- -----------------------------------------------------
-- ESTUDIO -> RESULTADOS -> IMAGEN
-- -----------------------------------------------------
CREATE TABLE estudio (
    id_estudio         INT AUTO_INCREMENT PRIMARY KEY,
    nombre             VARCHAR(100) NOT NULL,
    tipo_de_estudio    VARCHAR(50),
    es_por_referencia  BOOLEAN DEFAULT FALSE,
    duracion           INT,            -- en minutos
    preparacion        TEXT
);

CREATE TABLE resultados (
    id_resultado INT AUTO_INCREMENT PRIMARY KEY,
    tipo         VARCHAR(50),
    descripcion  TEXT,
    id_estudio   INT NOT NULL,         -- "genera"
    FOREIGN KEY (id_estudio) REFERENCES estudio(id_estudio)
);

CREATE TABLE imagen (
    id_imagen    INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100),
    url          VARCHAR(500) NOT NULL,
    id_resultado INT NOT NULL,         -- "contiene"
    FOREIGN KEY (id_resultado) REFERENCES resultados(id_resultado)
        ON DELETE CASCADE
);

-- -----------------------------------------------------
-- HISTORIAL CLÍNICO
-- -----------------------------------------------------
CREATE TABLE historial_clinico (
    id_hc       INT AUTO_INCREMENT PRIMARY KEY,
    tratamiento TEXT,
    nota        TEXT
);

-- -----------------------------------------------------
-- CITA (tabla central)
-- -----------------------------------------------------
CREATE TABLE cita (
    id_cita        INT AUTO_INCREMENT PRIMARY KEY,
    fecha          DATE NOT NULL,
    hora_inicio    TIME NOT NULL,
    hora_fin       TIME NOT NULL,
    estado         VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    id_paciente    INT NOT NULL,   -- "agenda"
    id_profesional INT NOT NULL,   -- "atiende en"
    id_sucursal    INT NOT NULL,   -- "esta en"
    id_estudio     INT NOT NULL,   -- "se realiza en"
    id_hc          INT,            -- "guarda en"
    FOREIGN KEY (id_paciente)    REFERENCES paciente(id_paciente),
    FOREIGN KEY (id_profesional) REFERENCES profesional(id_profesional),
    FOREIGN KEY (id_sucursal)    REFERENCES sucursal(id_sucursal),
    FOREIGN KEY (id_estudio)     REFERENCES estudio(id_estudio),
    FOREIGN KEY (id_hc)          REFERENCES historial_clinico(id_hc),
    CHECK (hora_fin > hora_inicio)
);

-- -----------------------------------------------------
-- ÍNDICES útiles
-- -----------------------------------------------------
CREATE INDEX idx_cita_fecha       ON cita(fecha);
CREATE INDEX idx_cita_paciente    ON cita(id_paciente);
CREATE INDEX idx_cita_profesional ON cita(id_profesional);
CREATE INDEX idx_orden_paciente   ON orden(id_paciente);

-- -----------------------------------------------------
-- DATOS DE PRUEBA
-- -----------------------------------------------------
INSERT INTO usuarios (nombre_completo, email, cedula, contrasena, rol, fecha_nacimiento, telefono)
VALUES ('Ana Pérez',  'ana@mail.com',  '1.234.567-8', 'hash_demo', 'paciente',    '1990-05-10', '099111222'),
       ('Dr. Luis Gómez', 'luis@mail.com', '2.345.678-9', 'hash_demo', 'profesional', '1980-03-02', '099333444');

INSERT INTO paciente (id_paciente) VALUES (1);
INSERT INTO profesional (id_profesional, telefono, tipo_de_profesional)
VALUES (2, '099333444', 'Radiólogo');

INSERT INTO sucursal (nombre, departamento, localidad, calle)
VALUES ('Sucursal Centro', 'Montevideo', 'Centro', '18 de Julio 1234');

INSERT INTO estudio (nombre, tipo_de_estudio, es_por_referencia, duracion, preparacion)
VALUES ('Radiografía de tórax', 'Rayos X', FALSE, 15, 'Sin preparación especial');

INSERT INTO historial_clinico (tratamiento, nota) VALUES ('Ninguno', 'Primera consulta');

INSERT INTO cita (fecha, hora_inicio, hora_fin, estado, id_paciente, id_profesional, id_sucursal, id_estudio, id_hc)
VALUES ('2026-11-02', '09:00', '09:15', 'pendiente', 1, 2, 1, 1, 1);

-- -----------------------------------------------------
-- CONSULTA DE PRUEBA
-- -----------------------------------------------------
SELECT c.fecha, c.hora_inicio, u.nombre_completo AS paciente,
       e.nombre AS estudio, s.nombre AS sucursal
FROM cita c
JOIN usuarios u  ON u.id_usuario = c.id_paciente
JOIN estudio e   ON e.id_estudio = c.id_estudio
JOIN sucursal s  ON s.id_sucursal = c.id_sucursal;
