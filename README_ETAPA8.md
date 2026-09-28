# Etapa 8 — Clasificación inteligente de correos mediante IA

## Incorpora
- Servicio de clasificación mediante OpenAI Responses API.
- Salida estructurada en JSON con prioridad, categoría, confianza y justificación.
- Clasificación individual desde el detalle del correo.
- Clasificación por lote de correos pendientes.
- Persistencia en `clasificaciones`.
- Cambio automático de `correos.estado` a `CLASIFICADO`.
- Uso de la última clasificación registrada para mostrar el resultado actual.
- Auditoría de las ejecuciones de clasificación.
- Protección CSRF en acciones POST.
- El contenido del correo se considera información no confiable y no puede imponer instrucciones al clasificador.

## Configuración
Agregar al `.env` existente:

```env
OPENAI_API_KEY="TU_API_KEY"
OPENAI_MODEL="gpt-5.6-luna"
OPENAI_TIMEOUT=60
OPENAI_MAX_EMAIL_CHARS=12000
```

No subir `.env` a GitHub. La clave API debe permanecer únicamente en el entorno local/servidor.

## Instalación
1. Realizar una copia de seguridad del proyecto actual.
2. Copiar esta etapa sobre la Etapa 7.
3. No reemplazar `.env`.
4. Agregar las variables de OpenAI indicadas arriba.
5. No ejecutar nuevamente el SQL de etapas anteriores.
6. Mantener `vendor/`.
7. Iniciar sesión con un usuario que tenga `clasificar_correos`.

## Clasificación individual
Desde `correo_ver.php`, utilizar **Clasificar con IA**.

## Clasificación por lote
Desde `correos.php`, elegir una cantidad de 5, 10 o 20 correos pendientes y ejecutar **Clasificar pendientes con IA**.

## Categorías utilizadas
- URGENTE
- FINANCIERO
- ADMINISTRATIVO
- OPERATIVO
- INFORMACION
- OTRO

La prioridad principal del sistema continúa siendo `ALTA`, `MEDIA` o `BAJA`.

## Nota de privacidad
La clasificación con un servicio de IA externo implica enviar al proveedor el contenido utilizado para el análisis. Antes de utilizar correos reales de la cooperativa en producción, debe verificarse la política institucional, contractual y de protección de datos aplicable.
