<?php
/**
 * Smoke test estático de Etapa 12.
 * Ejecutar desde la raíz del proyecto con:
 * php tests/smoke_test.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'index.php', 'login.php', 'logout.php', 'dashboard.php',
    'correos.php', 'correo_ver.php', 'cuentas_gmail.php',
    'gmail_sincronizar.php', 'clasificar_correo.php',
    'clasificar_pendientes.php', 'revisiones.php',
    'revision_ver.php', 'revision_guardar.php', 'estadisticas.php',
    'usuarios.php', 'usuario_form.php', 'usuario_guardar.php',
    'usuario_estado.php', '.htaccess', '.env.example',
    'config/bootstrap.php', 'config/security.php',
    'services/OpenAIClassificationService.php',
    'services/GmailApiService.php', 'services/TokenCipher.php'
];

$errors = [];
foreach ($required as $file) {
    if (!is_file($root . DIRECTORY_SEPARATOR . $file)) {
        $errors[] = "Falta archivo: {$file}";
    }
}

if (is_dir($root . DIRECTORY_SEPARATOR . 'vendor')) {
    echo "AVISO: vendor/ está incluido en el proyecto de prueba; el paquete Etapa 12 no lo distribuye.\n";
}

$env = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($env)) {
    echo "AVISO: existe .env en el entorno de ejecución; no debe publicarse ni incluirse en el ZIP.\n";
}

if ($errors) {
    echo "RESULTADO: FALLIDO\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "RESULTADO: OK\n";
echo "Archivos esenciales presentes: " . count($required) . "\n";
