# Sistema Correos IA - Etapa 4

Etapa 4: autenticación, sesiones, roles, permisos y protección básica.

## Requisitos

- XAMPP
- Apache
- MySQL
- PHP 8.1 o superior recomendado
- Base de datos `sistema_correos_ia`

## Instalación

1. Copiar la carpeta a:
   `C:\xampp\htdocs\sistema-correos-ia`

2. Verificar que Apache y MySQL estén iniciados.

3. Verificar que la base `sistema_correos_ia` y las tablas de la Etapa 2 existan.

4. Revisar `.env`.

5. Abrir:
   `http://localhost/sistema-correos-ia/setup_admin.php`

6. Crear el primer usuario Administrador con una contraseña elegida por el administrador.

7. ELIMINAR inmediatamente:
   `setup_admin.php`

8. Abrir:
   `http://localhost/sistema-correos-ia/login.php`

## Seguridad implementada

- `password_hash()` y `password_verify()`.
- PDO con consultas preparadas.
- Regeneración de ID de sesión después del login.
- Cookies de sesión `HttpOnly`.
- `SameSite=Lax`.
- Token CSRF para formularios POST.
- Control de autenticación.
- Control de permisos mediante roles.
- Mensajes genéricos para credenciales incorrectas.
- `.env` excluido de Git.

## Nota

La etapa 4 no implementa todavía:
- CRUD de usuarios.
- Gmail OAuth.
- Gmail API.
- clasificación mediante IA.
- dashboard funcional de correos.

Esas funciones pertenecen a etapas posteriores.
