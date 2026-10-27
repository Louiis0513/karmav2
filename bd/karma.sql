CREATE DATABASE IF NOT EXISTS karma
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE karma;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS mensaje;
DROP TABLE IF EXISTS conversacion;
DROP TABLE IF EXISTS solicitud;
DROP TABLE IF EXISTS tutor;
DROP TABLE IF EXISTS estudiante;
DROP TABLE IF EXISTS materia;
DROP TABLE IF EXISTS administrador;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE administrador (
  id_admin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materia (
  id_materia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  imagen VARCHAR(255) NULL,
  descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE estudiante (
  id_estudiante INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  telefono VARCHAR(10) NULL,
  grado VARCHAR(30) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutor (
  id_tutor INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  descripcion TEXT NULL,
  disponibilidad VARCHAR(150) NOT NULL,
  precio_hora DECIMAL(10,2) NULL,
  modalidad ENUM('virtual','presencial','ambas') NOT NULL DEFAULT 'virtual',
  telefono VARCHAR(10) NOT NULL,
  foto_url VARCHAR(255) NULL,
  verificado TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_materia INT UNSIGNED NOT NULL,
  FOREIGN KEY (id_materia) REFERENCES materia(id_materia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitud (
  id_solicitud INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_estudiante INT UNSIGNED NULL,
  nombre_estudiante VARCHAR(100) NOT NULL,
  telefono_estudiante VARCHAR(10) NOT NULL,
  descripcion_duda TEXT NULL,
  fecha DATE NOT NULL,
  medio ENUM('formulario', 'whatsapp') NOT NULL DEFAULT 'formulario',
  estado ENUM('pendiente','contactado','aceptado','rechazado','finalizado') NOT NULL DEFAULT 'pendiente',
  id_tutor INT UNSIGNED NOT NULL,
  FOREIGN KEY (id_tutor) REFERENCES tutor(id_tutor) ON DELETE CASCADE,
  FOREIGN KEY (id_estudiante) REFERENCES estudiante(id_estudiante) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conversacion (
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

CREATE TABLE mensaje (
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

-- Demo alumno: alumno@demo.com / Alumno123!
INSERT INTO estudiante (nombre, correo, password_hash, telefono, grado) VALUES
('Mariana López', 'alumno@demo.com', '$2y$10$zTEgJPwUrgR0wHbMOSuB3.jRXNSGeLdrnP79yy2FLxCJ/knTrbViC', '3009988776', '10°');

-- Demo: admin@demo.com / Admin123!
INSERT INTO administrador (nombre, correo, password_hash) VALUES
('Administradora KARMA', 'admin@demo.com', '$2y$10$0u3V6tivBysYiEaWduiJTe5sAAAPWeSKQz5gOya5TcxUCiC9OqRqi');

INSERT INTO materia (nombre, imagen, descripcion) VALUES
('Matemáticas', 'img/materias/matematicas.jpg', 'Álgebra, geometría y cálculo para bachillerato'),
('Inglés', 'img/materias/ingles.jpg', 'Gramática, listening y conversación'),
('Física', 'img/materias/fisica.jpg', 'Mecánica, energía y preparación ICFES'),
('Química', 'img/materias/quimica.jpg', 'Estequiometría, enlaces y laboratorio'),
('Biología', 'img/materias/biologia.jpg', 'Celular, genética y ecología'),
('Español', 'img/materias/espanol.jpg', 'Comprensión lectora y escritura');

-- Tutor demo con login: andres@demo.com / Tutor123! (los demás son solo de catálogo)
INSERT INTO tutor (nombre, correo, password_hash, descripcion, disponibilidad, precio_hora, modalidad, telefono, foto_url, verificado, id_materia) VALUES
('Andrés Salazar', 'andres@demo.com', '$2y$10$6rtRmzFt4mRjc30Penaat.WHud8/eq7HVUrZvRRipDBxduiLuO/sO', 'Estudiante de Ingeniería de Sistemas en la Universidad Nacional. Explico álgebra, funciones y pre-cálculo con ejemplos del ICFES. Paciente y con material propio en PDF.', 'Lunes a viernes, 3:00 a 6:00 p.m.', 25000, 'ambas', '3001112233', 'img/tutores/andres.jpg', 1, 1),
('Camila Reyes', NULL, NULL, 'Nivel B2 certificado. Apoyo en gramática, writing y preparación para pruebas de inglés. Ideal si necesitas ganar fluidez para el colegio o el ICFES.', 'Martes y jueves, 4:00 a 7:00 p.m.', 28000, 'virtual', '3004445566', 'img/tutores/camila.jpg', 1, 2),
('Julián Torres', NULL, NULL, 'Refuerzo en física mecánica, cinemática y leyes de Newton. Uso diagramas y ejercicios paso a paso para que no te pierdas en las fórmulas.', 'Sábados, 9:00 a.m. a 12:00 m.', 25000, 'virtual', '3017778899', 'img/tutores/julian.jpg', 1, 3),
('Valentina Gómez', NULL, NULL, 'Química de 10° y 11°. Estequiometría, balanço de ecuaciones y tipologías ICFES. Explico con analogías del día a día.', 'Miércoles y viernes, 2:00 a 5:00 p.m.', 25000, 'virtual', '3102223344', 'img/tutores/valentina.jpg', 1, 4),
('Daniel Herrera', NULL, NULL, 'Biología celular, genética mendeliana y ecología. Ayudo a armar mapas conceptuales y a resolver talleres del colegio.', 'Lunes y miércoles, 5:00 a 8:00 p.m.', 25000, 'virtual', '3125556677', 'img/tutores/daniel.jpg', 1, 5),
('Sofía Mendoza', NULL, NULL, 'Comprensión lectora, tipologías textuales y redacción de ensayos. Perfecta si te cuesta el componente de lenguaje del ICFES.', 'Martes a sábado, 10:00 a.m. a 1:00 p.m.', 25000, 'virtual', '3158889900', 'img/tutores/sofia.jpg', 1, 6),
('Mateo Ramírez', NULL, NULL, 'Matemáticas enfocada en geometría y estadística. Clases dinámicas con Geogebra y ejercicios tipo prueba.', 'Jueves y viernes, 4:00 a 7:00 p.m.', 25000, 'virtual', '3201234567', 'img/tutores/mateo.jpg', 1, 1),
('Laura Castillo', NULL, NULL, 'Inglés conversacional y refuerzo de listening. Sesiones cortas y prácticas para ganar confianza al hablar.', 'Sábados, 2:00 a 5:00 p.m.', 25000, 'virtual', '3019876543', 'img/tutores/laura.jpg', 0, 2);

INSERT INTO solicitud (nombre_estudiante, telefono_estudiante, descripcion_duda, fecha, medio, id_tutor) VALUES
('Mariana López', '3009988776', 'Necesito refuerzo en factorización y ecuaciones cuadráticas para el parcial del viernes.', CURDATE() - INTERVAL 2 DAY, 'formulario', 1),
('Santiago Díaz', '3112233445', 'Quiero mejorar mi speaking para una presentación oral en inglés.', CURDATE() - INTERVAL 1 DAY, 'whatsapp', 2),
('Isabella Ruiz', '3205566778', 'No entiendo cinemática: movimiento rectilíneo uniforme y acelerado.', CURDATE(), 'formulario', 3),
('Samuel Ortega', '3156677889', 'Ayuda con balanceo de ecuaciones y moles para el taller de química.', CURDATE() - INTERVAL 3 DAY, 'formulario', 4),
('Elena Vargas', '3001122334', 'Repaso de genética: cuadros de Punnett y herencia mendeliana.', CURDATE() - INTERVAL 4 DAY, 'whatsapp', 5);
