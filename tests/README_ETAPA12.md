# ETAPA 12 — Pruebas integrales del sistema

Esta etapa contiene la matriz de pruebas para validar el sistema de clasificación inteligente de correos.

## Alcance

Se cubren:
- autenticación y sesiones;
- autorización y permisos;
- usuarios;
- cuentas Gmail y OAuth;
- sincronización de correos;
- búsqueda y filtros;
- clasificación mediante IA;
- revisión humana;
- estadísticas;
- seguridad básica;
- responsividad.

## Importante

Las pruebas que dependen de XAMPP, MySQL, Gmail/OAuth y OpenAI deben ejecutarse en el entorno local del proyecto. No se deben marcar como aprobadas solo por inspección estática.

## Validación estática realizada

La versión entregada fue sometida a comprobación de sintaxis PHP con `php -l` sobre los archivos PHP del proyecto.

Resultado: 44 archivos PHP revisados, 0 errores de sintaxis.

## Ejecución funcional recomendada

1. Respaldar la base de datos `sistema_correos_ia`.
2. Iniciar Apache y MySQL.
3. Verificar que `.env` y `vendor/` del proyecto local se mantengan.
4. Ejecutar las pruebas de la matriz en orden.
5. Registrar evidencia mediante captura de pantalla o resultado observado.
6. No utilizar datos sensibles reales más allá de los necesarios para la prueba.
7. Al finalizar, registrar los casos fallidos y repetirlos después de corregirlos.
