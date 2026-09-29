# ETAPA 12 — PRUEBAS INTEGRALES

La Etapa 12 no agrega funcionalidades de negocio: valida las funcionalidades implementadas en las etapas anteriores.

## Incluido

- `tests/Matriz_Pruebas_Etapa12.xlsx`: matriz con 30 casos de prueba.
- `tests/README_ETAPA12.md`: instrucciones de ejecución y alcance.
- `tests/smoke_test.php`: comprobación estática de archivos esenciales.
- `php-lint-stage12.txt`: resultado de la revisión de sintaxis PHP.

## Resultado de validación estática

- Archivos PHP revisados con `php -l`: 44
- Errores de sintaxis: 0
- Archivos esenciales comprobados por smoke test: 25
- Resultado del smoke test: OK

## Pruebas funcionales

Las pruebas que requieren Apache, MySQL, Gmail/OAuth y OpenAI deben ejecutarse en el equipo donde está instalado el sistema. La matriz deja el estado como `Pendiente` hasta que se compruebe cada caso en ese entorno.

### Procedimiento

1. Respaldar la base de datos `sistema_correos_ia`.
2. Mantener el `.env` y `vendor/` del proyecto local.
3. Iniciar Apache y MySQL.
4. Abrir la aplicación y ejecutar la matriz en orden.
5. Registrar el resultado observado, estado y evidencia.
6. Corregir los casos fallidos y repetirlos.

No ejecutar nuevamente los scripts SQL de las etapas anteriores sobre una base de datos que ya contiene información.
