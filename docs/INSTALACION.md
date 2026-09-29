# Instalación local

## Requisitos
- Windows + XAMPP
- Apache
- MySQL/MariaDB
- PHP compatible con el proyecto
- Composer para reinstalar dependencias si fuera necesario

## Pasos
1. Copiar el proyecto en `C:\xampp\htdocs\sistema-correos-ia`.
2. Mantener el archivo `.env` de la instalación.
3. Mantener `vendor/` de la instalación; no forma parte del paquete distribuible.
4. Crear/verificar la base `sistema_correos_ia`. No ejecutar nuevamente scripts destructivos sobre una base con datos.
5. Configurar credenciales Google y OpenAI en `.env`.
6. Verificar el redirect URI de Google.
7. Iniciar Apache y MySQL.
8. Ejecutar `php tests/smoke_test.php` desde la raíz.
9. Probar login, Gmail, clasificación, revisión y estadísticas.
