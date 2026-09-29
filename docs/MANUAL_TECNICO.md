# Manual Técnico — Sistema Correos IA

## Arquitectura
Aplicación web PHP con MySQL, integración Gmail mediante Google OAuth/API y servicio de clasificación mediante OpenAI. La persistencia utiliza PDO y consultas preparadas.

## Componentes
- `config/`: configuración, sesión, CSRF, seguridad y conexión.
- `models/`: acceso a datos.
- `services/`: Gmail, OAuth, cifrado de tokens y clasificación IA.
- `controllers/`: lógica de autenticación y usuarios.
- `middleware/`: autenticación y permisos.
- `views/`: vistas compartidas.
- `tests/`: smoke test y matriz de pruebas.
- `docs/`: documentación final.

## Flujo funcional
Gmail → sincronización → correos → clasificación IA → revisión humana → prioridad final → estadísticas.

## Seguridad
Las credenciales se mantienen en `.env`; los tokens Gmail se cifran; se utilizan sesiones seguras, CSRF, consultas preparadas, control de permisos y encabezados de seguridad.

## Regla de revisión
`revisiones_clasificacion` conserva una revisión por clasificación para la operación de revisión actual; si existe, la acción modifica la revisión existente. La auditoría conserva el registro de la operación.
