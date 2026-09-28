# Sistema Correos IA — Etapa 6.2

Integración inicial con Google OAuth 2.0 y Gmail API para permitir conectar múltiples cuentas Gmail al sistema.

## Antes de instalar

- Debe estar instalada la Etapa 5 y la base de datos `sistema_correos_ia` debe conservarse.
- No vuelvas a importar el SQL de la Etapa 2.
- La cuenta de Google debe estar agregada como usuario de prueba en Google Auth Platform mientras la aplicación esté en modo de prueba.
- Gmail API debe estar habilitada.
- El scope utilizado en esta etapa es `https://www.googleapis.com/auth/gmail.readonly`.

## Instalación

1. Haz una copia de seguridad de tu carpeta actual `C:\xampp\htdocs\sistema-correos-ia`.
2. Conserva tu `.env` actual.
3. Copia los archivos de esta etapa combinando las carpetas y reemplazando los archivos incluidos.
4. Conserva la carpeta `vendor/` que ya creó Composer en tu proyecto local.
5. Si no tienes `composer.json`, conserva el incluido en esta etapa y ejecuta:

```bash
composer install
```

6. Agrega estas variables al `.env`:

```env
GOOGLE_CLIENT_ID="TU_CLIENT_ID"
GOOGLE_CLIENT_SECRET="TU_CLIENT_SECRET"
GOOGLE_REDIRECT_URI="http://localhost/sistema-correos-ia/oauth/google/callback.php"
GOOGLE_GMAIL_SCOPES="https://www.googleapis.com/auth/gmail.readonly"
APP_KEY="CLAVE_BASE64_DE_32_BYTES"
```

7. Genera `APP_KEY` desde la carpeta del proyecto:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Copia el resultado en `APP_KEY` del `.env`.

## Funcionalidad incorporada

- Inicio del flujo OAuth 2.0 de Google.
- Validación del parámetro `state` para protección CSRF.
- Solicitud de acceso offline para obtener `refresh_token`.
- Selección de cuenta Google.
- Obtención del correo autenticado mediante Gmail API.
- Registro/actualización de la cuenta en `cuentas_gmail`.
- Cifrado de `access_token` y `refresh_token` con Sodium antes de almacenarlos.
- Auditoría de conexión y actualización de cuentas Gmail.
- Pantalla `cuentas_gmail.php`.
- Botón «Conectar cuenta Gmail».
- Acceso protegido por el permiso `gestionar_cuentas_gmail`.
- Enlace desde el dashboard para usuarios que tengan dicho permiso.

## URL de prueba

Después de iniciar sesión como Administrador:

```text
http://localhost/sistema-correos-ia/cuentas_gmail.php
```

Luego selecciona:

**+ Conectar cuenta Gmail**

## Importante

No guardes `GOOGLE_CLIENT_SECRET` ni `APP_KEY` en GitHub. El archivo `.env` ya está excluido por `.gitignore`.

No se implementa todavía la sincronización de correos. Esa funcionalidad corresponde a la siguiente parte de la Etapa 6 y utilizará las cuentas conectadas aquí.
