-- Schema base para CRUD de libros de administrador
CREATE DATABASE IF NOT EXISTS muggle CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE muggle;

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS libros (
    id_libro INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NULL UNIQUE,
    doi VARCHAR(100) NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    portada VARCHAR(500) NULL,
    archivo VARCHAR(500) NULL,
    tipo ENUM('fisico', 'digital', 'audiolibro') NOT NULL DEFAULT 'digital',
    id_categoria INT NOT NULL,
    fecha_publicado DATE NULL,
    CONSTRAINT fk_libros_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categorias (id_categoria, nombre) VALUES
(1, 'General'),
(2, 'Ficcion'),
(3, 'No ficcion'),
(4, 'Fantasia'),
(5, 'Historia');
