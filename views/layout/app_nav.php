<?php
$navUsuario = usuarioActual();
$navPermiso = new Permiso(Conexion::getInstance());
$navUserId = (int) $navUsuario['id'];
$navTieneEstadisticas = $navPermiso->usuarioTienePermiso($navUserId, 'ver_estadisticas');
$navActual = basename($_SERVER['PHP_SELF']);
$navInicio = $navTieneEstadisticas ? 'estadisticas.php' : 'dashboard.php';
?>
<header class="app-header">
    <div class="app-header-top">
        <a class="app-brand" href="<?= e($navInicio) ?>">
            <span class="app-brand-mark">✉</span>
            <span><strong>Sistema de Correos IA</strong><small><?= e($navUsuario['rol_nombre']) ?></small></span>
        </a>
        <div class="app-user">
            <span class="app-user-name"><?= e($navUsuario['nombre'] . ' ' . $navUsuario['apellido']) ?></span>
            <form method="POST" action="logout.php" class="app-logout-form">
                <?= csrfInput() ?>
                <button type="submit" class="app-logout">Cerrar sesión</button>
            </form>
        </div>
    </div>
    <nav class="app-nav" aria-label="Navegación principal">
        <a class="<?= in_array($navActual, ['dashboard.php','estadisticas.php'], true) ? 'activo' : '' ?>" href="<?= e($navInicio) ?>">Inicio</a>
        <?php if ($navPermiso->usuarioTienePermiso($navUserId, 'ver_correos')): ?>
            <a class="<?= in_array($navActual, ['correos.php','correo_ver.php','clasificar_correo.php','clasificar_pendientes.php'], true) ? 'activo' : '' ?>" href="correos.php">Correos y clasificación</a>
        <?php endif; ?>
        <?php if ($navPermiso->usuarioTienePermiso($navUserId, 'gestionar_cuentas_gmail')): ?>
            <a class="<?= in_array($navActual, ['cuentas_gmail.php','gmail_sincronizar.php'], true) ? 'activo' : '' ?>" href="cuentas_gmail.php">Importar / gestionar Gmail</a>
        <?php endif; ?>
        <?php if ($navPermiso->usuarioTienePermiso($navUserId, 'revisar_clasificaciones')): ?>
            <a class="<?= in_array($navActual, ['revisiones.php','revision_ver.php','revision_guardar.php'], true) ? 'activo' : '' ?>" href="revisiones.php">Revisiones</a>
        <?php endif; ?>
        <?php if ($navPermiso->usuarioTienePermiso($navUserId, 'gestionar_usuarios')): ?>
            <a class="<?= in_array($navActual, ['usuarios.php','usuario_form.php','usuario_guardar.php','usuario_estado.php'], true) ? 'activo' : '' ?>" href="usuarios.php">Usuarios</a>
        <?php endif; ?>
    </nav>
</header>
