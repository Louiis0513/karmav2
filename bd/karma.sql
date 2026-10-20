CREATE DATABASE IF NOT EXISTS karma
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE karma;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS solicitud;
DROP TABLE IF EXISTS tutor;
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

CREATE TABLE tutor (
  id_tutor INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  disponibilidad VARCHAR(150) NOT NULL,
  telefono VARCHAR(10) NOT NULL,
  foto_url VARCHAR(255) NULL,
  verificado TINYINT(1) NOT NULL DEFAULT 0,
  id_materia INT UNSIGNED NOT NULL,
  FOREIGN KEY (id_materia) REFERENCES materia(id_materia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitud (
  id_solicitud INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_estudiante VARCHAR(100) NOT NULL,
  telefono_estudiante VARCHAR(10) NOT NULL,
  descripcion_duda TEXT NULL,
  fecha DATE NOT NULL,
  medio ENUM('formulario', 'whatsapp') NOT NULL DEFAULT 'formulario',
  id_tutor INT UNSIGNED NOT NULL,
  FOREIGN KEY (id_tutor) REFERENCES tutor(id_tutor) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO tutor (nombre, descripcion, disponibilidad, telefono, foto_url, verificado, id_materia) VALUES
('Andrés Salazar', 'Estudiante de Ingeniería de Sistemas en la Universidad Nacional. Explico álgebra, funciones y pre-cálculo con ejemplos del ICFES. Paciente y con material propio en PDF.', 'Lunes a viernes, 3:00 a 6:00 p.m.', '3001112233', 'img/tutores/andres.jpg', 1, 1),
('Camila Reyes', 'Nivel B2 certificado. Apoyo en gramática, writing y preparación para pruebas de inglés. Ideal si necesitas ganar fluidez para el colegio o el ICFES.', 'Martes y jueves, 4:00 a 7:00 p.m.', '3004445566', 'img/tutores/camila.jpg', 1, 2),
('Julián Torres', 'Refuerzo en física mecánica, cinemática y leyes de Newton. Uso diagramas y ejercicios paso a paso para que no te pierdas en las fórmulas.', 'Sábados, 9:00 a.m. a 12:00 m.', '3017778899', 'img/tutores/julian.jpg', 1, 3),
('Valentina Gómez', 'Química de 10° y 11°. Estequiometría, balanço de ecuaciones y tipologías ICFES. Explico con analogías del día a día.', 'Miércoles y viernes, 2:00 a 5:00 p.m.', '3102223344', 'img/tutores/valentina.jpg', 1, 4),
('Daniel Herrera', 'Biología celular, genética mendeliana y ecología. Ayudo a armar mapas conceptuales y a resolver talleres del colegio.', 'Lunes y miércoles, 5:00 a 8:00 p.m.', '3125556677', 'img/tutores/daniel.jpg', 1, 5),
('Sofía Mendoza', 'Comprensión lectora, tipologías textuales y redacción de ensayos. Perfecta si te cuesta el componente de lenguaje del ICFES.', 'Martes a sábado, 10:00 a.m. a 1:00 p.m.', '3158889900', 'img/tutores/sofia.jpg', 1, 6),
('Mateo Ramírez', 'Matemáticas enfocada en geometría y estadística. Clases dinámicas con Geogebra y ejercicios tipo prueba.', 'Jueves y viernes, 4:00 a 7:00 p.m.', '3201234567', 'img/tutores/mateo.jpg', 1, 1),
('Laura Castillo', 'Inglés conversacional y refuerzo de listening. Sesiones cortas y prácticas para ganar confianza al hablar.', 'Sábados, 2:00 a 5:00 p.m.', '3019876543', 'img/tutores/laura.jpg', 0, 2);

INSERT INTO solicitud (nombre_estudiante, telefono_estudiante, descripcion_duda, fecha, medio, id_tutor) VALUES
('Mariana López', '3009988776', 'Necesito refuerzo en factorización y ecuaciones cuadráticas para el parcial del viernes.', CURDATE() - INTERVAL 2 DAY, 'formulario', 1),
('Santiago Díaz', '3112233445', 'Quiero mejorar mi speaking para una presentación oral en inglés.', CURDATE() - INTERVAL 1 DAY, 'whatsapp', 2),
('Isabella Ruiz', '3205566778', 'No entiendo cinemática: movimiento rectilíneo uniforme y acelerado.', CURDATE(), 'formulario', 3),
('Samuel Ortega', '3156677889', 'Ayuda con balanceo de ecuaciones y moles para el taller de química.', CURDATE() - INTERVAL 3 DAY, 'formulario', 4),
('Elena Vargas', '3001122334', 'Repaso de genética: cuadros de Punnett y herencia mendeliana.', CURDATE() - INTERVAL 4 DAY, 'whatsapp', 5);
