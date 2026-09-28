<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Permiso.php';
require_once __DIR__ . '/../models/Correo.php';
require_once __DIR__ . '/../models/RevisionClasificacion.php';
require_once __DIR__ . '/../services/OpenAIClassificationService.php';
require_once __DIR__ . '/../services/ClasificacionService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/PermissionMiddleware.php';

iniciarSesionSegura();
