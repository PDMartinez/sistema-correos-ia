# Ajuste Etapa 10 - Dashboard principal

El dashboard principal del sistema ahora es el panel de estadísticas.

## Comportamiento

- Al iniciar sesión, `dashboard.php` redirige a `estadisticas.php` para usuarios con permiso `ver_estadisticas`.
- `estadisticas.php` incorpora un encabezado principal con navegación.
- Desde el encabezado se puede acceder, según los permisos del usuario, a:
  - Inicio / Dashboard
  - Correos y clasificación
  - Importar / gestionar Gmail
  - Revisiones
  - Usuarios
  - Cerrar sesión
- El módulo de estadísticas conserva sus filtros y métricas.
- Los usuarios sin permiso `ver_estadisticas` conservan un dashboard operativo básico.

## Instalación

Reemplazar los archivos del proyecto por esta versión conservando:

- `.env`
- `vendor/`
- datos de la base de datos

No ejecutar SQL.
