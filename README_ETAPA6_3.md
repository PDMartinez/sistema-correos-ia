# Etapa 6.3 — Sincronización e importación de correos Gmail

Esta etapa agrega la primera sincronización de mensajes de Gmail hacia la tabla `correos`.

## Qué incorpora

- Servicio `GmailApiService` para consumir Gmail API.
- Renovación automática del `access_token` usando el `refresh_token` cifrado cuando corresponde.
- Consulta configurable de Gmail.
- Paginación de `messages.list`.
- Recuperación del detalle con `messages.get`.
- Extracción de `From`, `To`, `Subject`, fecha y cuerpo.
- Soporte básico para `text/plain` y `text/html`.
- Evita duplicados usando `cuenta_gmail_id + gmail_message_id`.
- Guarda los mensajes con estado `NO_CLASIFICADO`.
- Actualiza `ultima_sincronizacion`.
- Registra auditoría de la sincronización.
- Botón `Sincronizar` en Gestionar cuentas Gmail.
- Todas las acciones de sincronización se realizan mediante POST + CSRF.

## Configuración `.env`

Agregar al `.env` existente, sin reemplazarlo:

```env
GMAIL_SYNC_MAX_MESSAGES=50
GMAIL_SYNC_QUERY="newer_than:30d -in:spam -in:trash"
```

Esto limita la primera sincronización a 50 mensajes como máximo y utiliza una consulta de los últimos 30 días, excluyendo spam y papelera. Se puede cambiar más adelante.

## Prueba

1. Copiar los archivos de esta etapa sobre el proyecto actual.
2. **No reemplazar `.env`**.
3. No importar nuevamente el SQL de la Etapa 2.
4. Conservar la carpeta `vendor/` creada por Composer.
5. Iniciar sesión como Administrador.
6. Entrar en `Gestionar cuentas Gmail`.
7. Confirmar que la cuenta esté `ACTIVA`.
8. Pulsar `Sincronizar`.
9. Revisar el mensaje de resultado.
10. Comprobar la tabla `correos` en phpMyAdmin.

## Resultado esperado

Los registros nuevos deben quedar con:

```text
cuenta_gmail_id    = ID de la cuenta conectada
gmail_message_id   = ID entregado por Gmail
gmail_thread_id    = threadId de Gmail
estado             = NO_CLASIFICADO
```

La segunda sincronización no debe volver a insertar esos mismos mensajes.

## Documentación oficial utilizada

Google indica que `messages.list` devuelve inicialmente los identificadores `id` y `threadId`, que `messages.get` debe utilizarse para recuperar el mensaje completo y que la respuesta puede paginarse mediante `nextPageToken`.
