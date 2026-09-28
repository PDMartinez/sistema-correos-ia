<?php
declare(strict_types=1);

function usuarioAutenticado(): bool
{
    iniciarSesionSegura();
    if (empty($_SESSION['usuario']['id'])) return false;

    // Comprobar el estado actual en la BD para revocar sesiones desactivadas.
    try {
        $model = new Usuario(Conexion::getInstance());
        $usuario = $model->obtenerPorId((int) $_SESSION['usuario']['id']);
        if (!$usuario || (int) $usuario['estado'] !== 1) {
            $_SESSION = [];
            return false;
        }
        $_SESSION['usuario']['rol_id'] = (int) $usuario['rol_id'];
        $_SESSION['usuario']['rol_nombre'] = $usuario['rol_nombre'];
        $_SESSION['usuario']['nombre'] = $usuario['nombre'];
        $_SESSION['usuario']['apellido'] = $usuario['apellido'];
        $_SESSION['usuario']['email'] = $usuario['email'];
        return true;
    } catch (Throwable $e) {
        error_log('Error al validar sesión: ' . $e->getMessage());
        return false;
    }
}

function exigirAutenticacion(): void
{
    if (!usuarioAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

function usuarioActual(): ?array
{
    iniciarSesionSegura();
    return $_SESSION['usuario'] ?? null;
}
