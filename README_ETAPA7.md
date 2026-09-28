# Etapa 7 — Gestión y visualización de correos

## Incorpora
- Listado paginado de correos importados.
- Búsqueda por remitente, destinatario y asunto.
- Filtros por cuenta Gmail y estado.
- Visualización del detalle de un correo.
- Control de acceso por cuenta Gmail: Administrador consulta cuentas activas; otros usuarios solo cuentas asignadas en `usuario_cuenta_gmail`.
- Escape del contenido del correo para evitar ejecución de HTML/JavaScript recibido.
- Auditoría de la visualización de un correo.
- Enlace desde el dashboard.

## Archivos principales
- `models/Correo.php`
- `correos.php`
- `correo_ver.php`
- `assets/css/estilos.css`

## Instalación
1. Realizar una copia de seguridad del proyecto actual.
2. Copiar los archivos de esta etapa sobre el proyecto actual.
3. No reemplazar `.env`.
4. No importar nuevamente ningún SQL de etapas anteriores.
5. Mantener `vendor/`.
6. Iniciar sesión y entrar en `Ver correos`.

## Nota sobre acceso
El Administrador puede visualizar correos de las cuentas Gmail activas. Los demás usuarios necesitan una fila en `usuario_cuenta_gmail` para cada cuenta a la que deban acceder. La interfaz de asignación de cuentas a usuarios se puede incorporar como una mejora administrativa posterior.
