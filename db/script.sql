-- Script consolidado para biblioteca_digital
CREATE DATABASE IF NOT EXISTS biblioteca_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_digital;

CREATE TABLE IF NOT EXISTS roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NULL,
    google_id VARCHAR(255) NULL,
    foto_perfil VARCHAR(255) NULL,
    tema_habilitado TINYINT(1) NOT NULL DEFAULT 1,
    casa_preferida VARCHAR(20) NOT NULL DEFAULT 'ravenclaw',
    estado ENUM('activo', 'inactivo', 'baneado') NOT NULL DEFAULT 'activo',
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_rol INT NULL,
    CONSTRAINT fk_usuarios_roles
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ELIMINADO:
-- ALTER TABLE usuarios
--     ADD COLUMN IF NOT EXISTS tema_habilitado TINYINT(1) NOT NULL DEFAULT 1,
--     ADD COLUMN IF NOT EXISTS casa_preferida VARCHAR(20) NOT NULL DEFAULT 'ravenclaw';

CREATE TABLE IF NOT EXISTS auth_google_logs (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    google_id VARCHAR(255) NULL,
    email VARCHAR(150) NULL,
    ip VARCHAR(45) NULL,
    dispositivo VARCHAR(255) NULL,
    estado_login ENUM('exitoso', 'fallido') NOT NULL,
    fecha_login TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auth_google_logs_usuarios
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS banners (
    id_banner INT AUTO_INCREMENT PRIMARY KEY,
    imagen VARCHAR(500) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS libros (
    id_libro INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NULL UNIQUE,
    doi VARCHAR(100) NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NULL,
    descripcion TEXT NULL,
    portada VARCHAR(500) NULL,
    archivo VARCHAR(500) NULL,
    tipo ENUM('fisico', 'digital', 'audiolibro', 'pdf', 'epub') NOT NULL DEFAULT 'digital',
    id_categoria INT NOT NULL,
    id_banner INT NULL,
    fecha_publicado DATE NULL,
    UNIQUE KEY uq_libros_id_banner (id_banner),
    CONSTRAINT fk_libros_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_libros_banner
        FOREIGN KEY (id_banner) REFERENCES banners(id_banner)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS planes (
    id_plan INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descripcion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS suscripciones (
    id_suscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_plan INT NOT NULL,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    estado ENUM('activa', 'vencida', 'cancelada') NOT NULL DEFAULT 'activa',
    CONSTRAINT fk_suscripciones_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_suscripciones_plan
        FOREIGN KEY (id_plan) REFERENCES planes(id_plan)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS progreso_lectura (
    id_progreso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_libro INT NOT NULL,
    pagina_actual INT NOT NULL DEFAULT 0,
    porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_progreso_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_progreso_libro
        FOREIGN KEY (id_libro) REFERENCES libros(id_libro)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favoritos (
    id_favorito INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_libro INT NOT NULL,
    fecha_agregado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_favoritos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_favoritos_libro
        FOREIGN KEY (id_libro) REFERENCES libros(id_libro)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs_sistema (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    accion VARCHAR(255) NULL,
    descripcion TEXT NULL,
    ip VARCHAR(45) NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS twofa_auth (
    id_2fa INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    activado BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_activacion TIMESTAMP NULL,
    CONSTRAINT fk_twofa_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (id_rol, nombre, estado) VALUES
(1, 'admin', 'activo'),
(2, 'usuario', 'activo');

INSERT IGNORE INTO categorias (id_categoria, nombre, descripcion) VALUES
(1, 'General', 'Categoria general'),
(2, 'Ficcion', 'Novelas y relatos de ficcion'),
(3, 'No ficcion', 'Contenido basado en hechos reales'),
(4, 'Fantasia', 'Historias fantasticos y mundos magicos'),
(5, 'Historia', 'Libros historicos y biograficos');

INSERT IGNORE INTO planes (id_plan, nombre, precio, descripcion) VALUES
(1, 'Free',    0.00,  'Acceso a libros gratuitos'),
(2, 'Básico',  4.99,  'Catálogo básico'),
(3, 'Plus',    8.99,  'Catálogo ampliado con audiolibros parcial'),
(4, 'Premium', 13.99, 'Catálogo completo con audiolibros');

INSERT IGNORE INTO libros (isbn, doi, titulo, autor, descripcion, portada, archivo, tipo, id_categoria, id_banner, fecha_publicado) VALUES
('9780747532699', NULL, 'Harry Potter and the Philosopher''s Stone', 'J.K. Rowling', 'Primera entrega de la saga Harry Potter.', 'https://covers.openlibrary.org/b/isbn/9780747532699-L.jpg', NULL, 'digital', 4, NULL, '1997-06-26'),
('9780439064873', NULL, 'Harry Potter and the Chamber of Secrets', 'J.K. Rowling', 'Segunda entrega de la saga Harry Potter.', 'https://covers.openlibrary.org/b/isbn/9780439064873-L.jpg', NULL, 'digital', 4, NULL, '1998-07-02'),
('9780439139601', NULL, 'Harry Potter and the Goblet of Fire', 'J.K. Rowling', 'Cuarta entrega de la saga Harry Potter.', 'https://covers.openlibrary.org/b/isbn/9780439139601-L.jpg', NULL, 'digital', 4, NULL, '2000-07-08');

INSERT INTO usuarios (nombre, email, password, estado, id_rol, tema_habilitado, casa_preferida)
VALUES
('Administrador Hogwarts', 'admin@hogwarts.local', '$2y$12$VIA/jN9xZ9beEIhJ8YEFL.0HXDjSImwOoICzifc4gEKK.Y6/PCXaG', 'activo', 1, 1, 'ravenclaw'),
('Harry Usuario', 'harry@hogwarts.local', '$2y$12$JyYvw1RbFY36OBgpr2CpRug5BSLVE/BKzX.lvzsMx06suIl6N8FH2', 'activo', 2, 1, 'gryffindor'),
('Hermione Lectora', 'hermione@hogwarts.local', '$2y$12$dqgdAjexUdQ/2H4JsiUML.DZB.v7gyXDGoYKm4Xr8Nzu6aVVn8aui', 'activo', 2, 0, 'ravenclaw')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    estado = VALUES(estado),
    id_rol = VALUES(id_rol),
    tema_habilitado = VALUES(tema_habilitado),
    casa_preferida = VALUES(casa_preferida);

INSERT INTO suscripciones (id_usuario, id_plan, fecha_inicio, estado)
SELECT u.id_usuario, 4, CURDATE(), 'activa'
FROM usuarios u
LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = 'activa'
WHERE u.email = 'admin@hogwarts.local' AND s.id_suscripcion IS NULL;

INSERT INTO suscripciones (id_usuario, id_plan, fecha_inicio, estado)
SELECT u.id_usuario, 2, CURDATE(), 'activa'
FROM usuarios u
LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = 'activa'
WHERE u.email = 'harry@hogwarts.local' AND s.id_suscripcion IS NULL;

INSERT INTO suscripciones (id_usuario, id_plan, fecha_inicio, estado)
SELECT u.id_usuario, 1, CURDATE(), 'activa'
FROM usuarios u
LEFT JOIN suscripciones s ON s.id_usuario = u.id_usuario AND s.estado = 'activa'
WHERE u.email = 'hermione@hogwarts.local' AND s.id_suscripcion IS NULL;