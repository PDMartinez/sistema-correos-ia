<?php
declare(strict_types=1);

function usuarioAutenticado(): bool
{
    iniciarSesionSegura();

    return isset($_SESSION['usuario']['id']);
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
