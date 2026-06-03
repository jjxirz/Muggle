-- 1. Borra la base de datos por completo si ya existía de pruebas anteriores
DROP DATABASE IF EXISTS muggle;

-- 2. Crea la base de datos limpia con el formato de texto correcto
CREATE DATABASE IF NOT EXISTS muggle
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 3. Selecciona la base de datos para empezar a crear las tablas
USE muggle;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS logs_sistema;
DROP TABLE IF EXISTS reportes;
DROP TABLE IF EXISTS progreso_lectura;
DROP TABLE IF EXISTS lista_lectura;
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS suscripciones;
DROP TABLE IF EXISTS banners;
DROP TABLE IF EXISTS libros;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS planes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

-- Roles
CREATE TABLE roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255)
) ENGINE=InnoDB;

-- Usuarios
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  auth_provider ENUM('local','google') NOT NULL DEFAULT 'local',
  google_sub VARCHAR(64) NULL,
  google_picture_url VARCHAR(255) NULL,
  prueba_7d_usada TINYINT(1) NOT NULL DEFAULT 0,
  estado ENUM('activo','inactivo','baneado') NOT NULL DEFAULT 'activo',
  id_rol INT,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  tema_habilitado TINYINT(1) NOT NULL DEFAULT 1,
  casa_preferida VARCHAR(20) NOT NULL DEFAULT 'ravenclaw',
  UNIQUE KEY uq_usuarios_google_sub (google_sub),
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB;

-- Planes
CREATE TABLE planes (
  id_plan INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL UNIQUE,
  precio DECIMAL(10,2) NOT NULL,
  descripcion TEXT,
  duracion_dias INT NOT NULL DEFAULT 30,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categorias
CREATE TABLE categorias (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion TEXT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Libros
CREATE TABLE libros (
  id_libro INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  autor VARCHAR(180),
  descripcion TEXT,
  portada VARCHAR(255),
  archivo VARCHAR(255),
  tipo ENUM('fisico','digital','audiolibro','pdf','epub') NOT NULL DEFAULT 'digital',
  fecha_publicado DATE,
  id_categoria INT,
  id_banner INT NULL,
  id_plan_minimo INT NULL,
  isbn VARCHAR(40) NULL,
  doi VARCHAR(80) NULL,
  estado_publicacion ENUM('borrador','publicado','oculto') NOT NULL DEFAULT 'publicado',
  fecha_publicacion_programada DATETIME NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE SET NULL,
  FOREIGN KEY (id_plan_minimo) REFERENCES planes(id_plan) ON DELETE SET NULL,
  INDEX idx_libros_titulo (titulo),
  INDEX idx_libros_estado (estado_publicacion),
  UNIQUE KEY uq_libros_isbn (isbn),
  UNIQUE KEY uq_libros_doi (doi)
) ENGINE=InnoDB;

-- Banners
CREATE TABLE banners (
  id_banner INT AUTO_INCREMENT PRIMARY KEY,
  imagen VARCHAR(500) NOT NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE libros
  ADD CONSTRAINT fk_libros_banner
  FOREIGN KEY (id_banner) REFERENCES banners(id_banner)
  ON DELETE SET NULL;

-- Suscripciones
CREATE TABLE suscripciones (
  id_suscripcion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_plan INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NULL,
  estado ENUM('activa','cancelada','expirada') NOT NULL DEFAULT 'activa',
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_plan) REFERENCES planes(id_plan)
) ENGINE=InnoDB;

-- Favoritos
CREATE TABLE favoritos (
  id_favorito INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_libro INT NOT NULL,
  fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_libro) REFERENCES libros(id_libro) ON DELETE CASCADE,
  UNIQUE KEY uq_favorito_usuario_libro (id_usuario, id_libro),
  INDEX idx_favoritos_usuario (id_usuario),
  INDEX idx_favoritos_libro (id_libro)
) ENGINE=InnoDB;

-- Lista de lectura
CREATE TABLE lista_lectura (
  id_lista_lectura INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_libro INT NOT NULL,
  fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_libro) REFERENCES libros(id_libro) ON DELETE CASCADE,
  UNIQUE KEY uq_lista_usuario_libro (id_usuario, id_libro),
  INDEX idx_lista_usuario (id_usuario),
  INDEX idx_lista_libro (id_libro)
) ENGINE=InnoDB;

-- Progreso de lectura
CREATE TABLE progreso_lectura (
  id_progreso INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_libro INT NOT NULL,
  progreso DECIMAL(5,2) DEFAULT 0,
  porcentaje DECIMAL(5,2) DEFAULT 0,
  pagina_actual INT DEFAULT 1,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_libro) REFERENCES libros(id_libro) ON DELETE CASCADE,
  UNIQUE KEY uq_progreso_usuario_libro (id_usuario, id_libro)
) ENGINE=InnoDB;

-- Reportes
CREATE TABLE reportes (
  id_reporte INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  tipo ENUM('libro','usuario','sistema') NOT NULL,
  descripcion TEXT,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Logs del sistema
CREATE TABLE logs_sistema (
  id_log BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NULL,
  modulo VARCHAR(60) NOT NULL,
  accion VARCHAR(120) NOT NULL,
  detalle TEXT NULL,
  metadata JSON NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_modulo_fecha (modulo, fecha),
  INDEX idx_logs_usuario_fecha (id_usuario, fecha),
  INDEX idx_logs_accion (accion),
  CONSTRAINT fk_logs_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Seed minimo
-- =====================================================

INSERT INTO roles (id_rol, nombre, descripcion) VALUES
  (1, 'admin', 'Administrador del sistema'),
  (2, 'usuario', 'Usuario de plataforma');

-- Admin inicial
-- email: admin@muggle.local
-- password: Admin123!
INSERT INTO usuarios (id_usuario, nombre, email, password, auth_provider, estado, id_rol, tema_habilitado, casa_preferida, prueba_7d_usada)
VALUES (
  1,
  'Administrador',
  'admin@muggle.local',
  '$2y$12$awD9IIcJMuh/YgEoTpVRKeRzHYCWSgI3muTP70qi20hUoXpLFP3wu',
  'local',
  'activo',
  1,
  1,
  'ravenclaw',
  1
);

INSERT IGNORE INTO planes (id_plan, nombre, precio, descripcion, duracion_dias) VALUES
  (5, 'Prueba 7 dias', 0.00, 'Prueba gratuita por 7 dias con beneficios similares al plan Basico', 7);
