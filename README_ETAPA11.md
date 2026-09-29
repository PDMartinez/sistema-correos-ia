# Etapa 11 — Seguridad y revisión integral

Esta etapa aplica una revisión de seguridad sobre la versión funcional anterior sin modificar la estructura de la base de datos.

## Cambios principales

- Encabezados HTTP de seguridad: CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy y Permissions-Policy.
- HSTS cuando la aplicación se ejecuta mediante HTTPS.
- Cookies de sesión con `HttpOnly`, `SameSite=Lax`, modo estricto y sin URL rewriting de sesión.
- Regeneración del ID de sesión al autenticar y renovación del token CSRF asociado.
- Mensajes técnicos detallados enviados al registro del servidor en lugar de mostrarse al usuario.
- Protección adicional de `.env` y archivos `.log` mediante `.htaccess` y deshabilitación de listado de directorios.
- `.env.example` preparado con `APP_DEBUG=false` como valor seguro de referencia.
- Se mantienen las consultas PDO preparadas y los controles de permisos existentes.
- Se mantienen OAuth `state`, cifrado Sodium de tokens Gmail y la separación por cuentas Gmail.

## Instalación

1. Reemplazar los archivos sobre el proyecto actual.
2. Conservar el `.env` real.
3. Conservar `vendor/` en la instalación local.
4. No ejecutar SQL.
5. Reiniciar Apache después de copiar los archivos.

## Verificación recomendada

- Intentar abrir `.env` desde el navegador: debe responder con acceso denegado.
- Intentar acceder a módulos sin el permiso correspondiente.
- Intentar enviar formularios POST sin CSRF.
- Verificar que errores de OpenAI, Gmail o PDO no expongan detalles técnicos en pantalla.
- Probar logout y acceso posterior a páginas protegidas.
- Probar usuario desactivado con una sesión previamente iniciada.
