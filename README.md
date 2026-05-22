# Hogwarts

Aplicación web de biblioteca digital en PHP con:

- Inicio de sesión por usuario/contraseña
- Catálogo principal (libros hardcodeados por archivos PDF en `assets/books`)
- Lector PDF con guardado de progreso
- Favoritos por usuario
- Panel administrativo (dashboard, usuarios, catálogo)

## Requisitos

- PHP 8.1+
- MySQL 8+
- Servidor web (Apache/Nginx)

## Configuración rápida

1. Crear base de datos y tablas:

```sql
SOURCE db/script.sql;
```

2. Configurar variables de entorno del servidor PHP:

- `DB_HOST` (default `127.0.0.1`)
- `DB_PORT` (default `3306`)
- `DB_NAME` (default `biblioteca_digital`)
- `DB_USER` (default `root`)
- `DB_PASS` (default vacío)
- `APP_BASE_URL` (default `/Muggle`)

3. Asegurar permisos de escritura en:

- `assets/books`
- `assets/banners`

## Usuarios de ejemplo

- Administrador
	- Email: `admin@hogwarts.local`
	- Password: `Admin123!`

- Usuario 1
	- Email: `harry@hogwarts.local`
	- Password: `Usuario123!`

- Usuario 2
	- Email: `hermione@hogwarts.local`
	- Password: `Lectura123!`

## Seguridad implementada

- Sesión regenerada en login
- Validación de rol admin contra base de datos
- Tokens CSRF en formularios administrativos y APIs de interacción
- Logout con limpieza completa de sesión/cookie

## Flujo de temas

- El tema por casa es opcional por usuario.
- Se configura desde el perfil.
- Si se desactiva, la interfaz usa tema clásico de Hogwarts.
