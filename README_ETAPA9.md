# Etapa 9 — Revisión humana de clasificaciones

## Incorpora
- Bandeja de revisión de clasificaciones generadas por IA.
- Filtros por cuenta Gmail, prioridad IA, categoría, confianza mínima, estado de revisión y búsqueda.
- Control de acceso por cuentas Gmail autorizadas.
- Vista detallada del correo y de la clasificación IA.
- Registro de prioridad final mediante `revisiones_clasificacion`.
- Registro de `prioridad_anterior`, `prioridad_final`, comentario, usuario y fecha.
- Historial completo de revisiones para cada clasificación.
- Después de una revisión, el correo pasa a estado `ATENDIDO`.
- Una nueva revisión no elimina la anterior: se conserva el historial.
- Permiso `revisar_clasificaciones` para consultar.
- Permiso `modificar_clasificacion` para registrar/cambiar la prioridad final.
- Protección CSRF y auditoría.
- No requiere cambios en la base de datos.

## Instalación
1. Realizar copia de seguridad de la Etapa 8.
2. Copiar esta etapa sobre el proyecto actual.
3. Conservar `.env` y `vendor/`.
4. No ejecutar nuevamente el SQL de etapas anteriores.
5. Iniciar sesión con un usuario que tenga `revisar_clasificaciones`.
6. Para registrar una decisión final, el usuario necesita además `modificar_clasificacion`.

## Flujo
Gmail → Correo → Clasificación IA → Revisión humana → Prioridad final → Atendido.

## Ajuste posterior: revisión única vigente

La revisión humana de una clasificación es única y vigente por clasificación. Si el correo ya fue revisado, el formulario cambia a **Modificar revisión** y actualiza la misma fila de `revisiones_clasificacion` en lugar de insertar una segunda revisión. La prioridad propuesta por la IA (`prioridad_anterior`) se conserva y la decisión humana vigente queda en `prioridad_final`.

Cada registro o modificación sigue generando una entrada independiente en `auditoria`, por lo que las acciones administrativas quedan trazables sin duplicar la revisión vigente.

### Dependencias

La carpeta `vendor/` no se incluye en este paquete. Debe conservarse la carpeta `vendor/` de tu instalación actual, generada por Composer en la Etapa 6.2.
