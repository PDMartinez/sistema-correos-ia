# Sistema Correos IA — Etapa 5

Módulo de gestión de usuarios integrado con las etapas 2–4.

## Instalación
1. Conserva tu base de datos `sistema_correos_ia`. **NO vuelvas a importar `sistema.sql`**: el script de la Etapa 2 elimina y recrea tablas.
2. Haz una copia de seguridad de la carpeta actual del proyecto.
3. Copia los archivos de este ZIP a `C:\xampp\htdocs\sistema-correos-ia`, combinando las carpetas y reemplazando los archivos de código.
4. **Conserva tu `.env` actual**: el ZIP contiene una plantilla local de desarrollo; no sustituyas tus credenciales reales.
5. Comprueba que `setup_admin.php` esté eliminado: si lo copiaste desde el ZIP, elimínalo inmediatamente.
6. Inicia Apache y MySQL y accede a `http://localhost/sistema-correos-ia/login.php`.
7. Inicia sesión con tu administrador y abre «Gestionar usuarios».

## Funciones
- Listado y búsqueda (primeros 200 resultados).
- Alta de usuarios, con contraseña hasheada.
- Edición de nombre, apellido, correo, rol y contraseña opcional.
- Activar y desactivar usuarios (sin eliminación física).
- Verificación del permiso `gestionar_usuarios` en el servidor.
- Validación de formularios y token CSRF.
- Auditoría de altas, cambios y cambios de estado.
- No permite desactivar la propia cuenta, cambiar el propio rol ni quitar el último administrador activo.
- Las sesiones de usuarios desactivados se invalidan en la siguiente solicitud.

## Pruebas manuales
1. Crear un usuario Cajero con contraseña de 12 o más caracteres.
2. Iniciar sesión con ese usuario: no debe poder abrir `usuarios.php`.
3. Editar su nombre/correo/rol desde la cuenta Administrador.
4. Dejar vacía la contraseña al editar: debe mantenerse la anterior.
5. Desactivar al Cajero y comprobar que ya no puede iniciar sesión.
6. Comprobar que no puedes desactivar tu propio administrador.
7. Verificar las acciones en la tabla `auditoria`.

## Seguridad
- El archivo `setup_admin.php` es solo para instalación inicial: debe eliminarse.
- El módulo requiere HTTPS y credenciales de BD no privilegiadas antes de producción.
- No se implementa aún restablecimiento de contraseña ni control de intentos fallidos; debe incorporarse antes del despliegue.
