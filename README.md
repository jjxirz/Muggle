# Hogwarts

Aplicación web de biblioteca digital en PHP con:

- Inicio de sesión con Google para usuarios
- Inicio de sesión local solo para administrador
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
- `DB_NAME` (default `muggle`)
- `DB_USER` (default `root`)
- `DB_PASS` (default `almas12`)
- `APP_BASE_URL` (default `/Muggle`)
- `GOOGLE_CLIENT_ID` (requerido para login Google)
- `ADMIN_EMAIL` (default `admin@muggle.local`)

3. Asegurar permisos de escritura en:

- `assets/books`
- `assets/banners`

## Usuarios de ejemplo

- Administrador
	- Email: `admin@muggle.local`
	- Password: `Admin123!`

Los usuarios normales se crean/inician con Google y pueden activar una prueba gratuita de 7 días (una sola vez).

## Seguridad implementada

- Sesión regenerada en login
- Validación de rol admin contra base de datos
- Tokens CSRF en formularios administrativos y APIs de interacción
- Logout con limpieza completa de sesión/cookie

## Flujo de temas

- El tema por casa es opcional por usuario.
- Se configura desde el perfil.
- Si se desactiva, la interfaz usa tema clásico de Hogwarts.
