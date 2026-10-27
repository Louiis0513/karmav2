-- KARMA · Migración: registro/login de tutores y alumnos, chat, precio/modalidad
-- Importar en phpMyAdmin sobre la BD `karma` ya existente (una sola vez).
USE karma;

-- 1) Tutor: credenciales de login + datos comerciales
ALTER TABLE tutor
  ADD COLUMN correo VARCHAR(150) NULL UNIQUE AFTER nombre,
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER correo,
  ADD COLUMN precio_hora DECIMAL(10,2) NULL AFTER disponibilidad,
  ADD COLUMN modalidad ENUM('virtual','presencial','ambas') NOT NULL DEFAULT 'virtual' AFTER precio_hora,
  ADD COLUMN creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 2) Alumnos
CREATE TABLE IF NOT EXISTS estudiante (
  id_estudiante INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  telefono VARCHAR(10) NULL,
  grado VARCHAR(30) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Solicitud: vinculada al alumno logueado (si lo hay) + estado
ALTER TABLE solicitud
  ADD COLUMN id_estudiante INT UNSIGNED NULL AFTER id_solicitud,
  ADD COLUMN estado ENUM('pendiente','contactado','aceptado','rechazado','finalizado') NOT NULL DEFAULT 'pendiente' AFTER medio,
  ADD CONSTRAINT fk_solicitud_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiante(id_estudiante) ON DELETE SET NULL;

-- 4) Chat alumno <-> tutor
CREATE TABLE IF NOT EXISTS conversacion (
  id_conversacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_estudiante INT UNSIGNED NOT NULL,
  id_tutor INT UNSIGNED NOT NULL,
  tema VARCHAR(255) NULL,
  fecha_propuesta VARCHAR(100) NULL,
  modalidad ENUM('virtual','presencial') NULL,
  estado ENUM('abierta','acordada','cerrada') NOT NULL DEFAULT 'abierta',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conversacion_par (id_estudiante, id_tutor),
  FOREIGN KEY (id_estudiante) REFERENCES estudiante(id_estudiante) ON DELETE CASCADE,
  FOREIGN KEY (id_tutor) REFERENCES tutor(id_tutor) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensaje (
  id_mensaje INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_conversacion INT UNSIGNED NOT NULL,
  remitente_tipo ENUM('estudiante','tutor') NOT NULL,
  id_remitente INT UNSIGNED NOT NULL,
  contenido TEXT NOT NULL,
  leido TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_conversacion) REFERENCES conversacion(id_conversacion) ON DELETE CASCADE,
  INDEX idx_mensaje_conv (id_conversacion, id_mensaje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
