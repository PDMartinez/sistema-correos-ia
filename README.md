# Sistema Correos IA

Proyecto web para la clasificación automática de correos electrónicos mediante Inteligencia Artificial.

## Etapa 3

Esta etapa implementa:

- Estructura inicial del proyecto.
- Configuración mediante `.env`.
- Conexión centralizada a MySQL mediante PDO.
- Página de prueba de conexión.

## Requisitos

- XAMPP
- Apache
- MySQL
- PHP 8.1 o superior recomendado
- Base de datos `sistema_correos_ia`

## Instalación

1. Copiar la carpeta `sistema-correos-ia` dentro de:

   `C:\xampp\htdocs\`

2. Verificar que Apache y MySQL estén iniciados.

3. Verificar que exista la base de datos:

   `sistema_correos_ia`

4. Revisar `.env`.

5. Abrir:

   `http://localhost/sistema-correos-ia/`

## Nota de seguridad

El archivo `.env` contiene configuración sensible y no debe subirse a Git. El archivo `.env.example` sirve como plantilla.
