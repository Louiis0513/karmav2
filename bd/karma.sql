CREATE DATABASE IF NOT EXISTS karma
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE karma;

CREATE TABLE IF NOT EXISTS administrador (
  id_admin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS materia (
  id_materia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tutor (
  id_tutor INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  disponibilidad VARCHAR(150) NOT NULL,
  telefono VARCHAR(10) NOT NULL,
  foto_url VARCHAR(255) NULL,
  verificado TINYINT(1) NOT NULL DEFAULT 0,
  id_materia INT UNSIGNED NOT NULL,
  FOREIGN KEY (id_materia) REFERENCES materia(id_materia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitud (
  id_solicitud INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_estudiante VARCHAR(100) NOT NULL,
  telefono_estudiante VARCHAR(10) NOT NULL,
  descripcion_duda TEXT NULL,
  fecha DATE NOT NULL,
  medio ENUM('formulario', 'whatsapp') NOT NULL DEFAULT 'formulario',
  id_tutor INT UNSIGNED NOT NULL,
  FOREIGN KEY (id_tutor) REFERENCES tutor(id_tutor) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Datos de ejemplo
-- Administrador: admin@karma.com / admin12345
INSERT INTO administrador (nombre, correo, password_hash) VALUES
('Administrador KARMA', 'admin@karma.com', '$2y$10$8VU8w8uXAxuXR0e7fQsveO4ECKexwFcoDP7rIcjkrxUzziUClb23S');

INSERT INTO materia (nombre) VALUES
('Matemáticas'), ('Inglés'), ('Física'), ('Química');

INSERT INTO tutor (nombre, descripcion, disponibilidad, telefono, verificado, id_materia) VALUES
('Andrés Salazar', 'Estudiante de ingeniería, explica álgebra y cálculo.', 'Lunes a viernes, 3 a 6 p.m.', '3001112233', 1, 1),
('Camila Reyes', 'Nivel B2, apoyo en gramática y conversación.', 'Martes y jueves, 4 a 7 p.m.', '3004445566', 1, 2),
('Julián Torres', 'Refuerzo en física mecánica.', 'Sábados, 9 a.m. a 12 m.', '3017778899', 0, 3);
