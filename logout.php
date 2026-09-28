<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/config/conexion.php';

iniciarSesionSegura();

$pdo = Conexion::getInstance();
$usuarioModel = new Usuario($pdo);
$auth = new AuthController($usuarioModel);

$auth->cerrarSesion();

header('Location: login.php');
exit;
