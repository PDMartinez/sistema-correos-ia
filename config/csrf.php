<?php
declare(strict_types=1);

/**
 * Token CSRF para formularios POST.
 */
function csrfToken(): string
{
    iniciarSesionSegura();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarCsrf(?string $token): bool
{
    iniciarSesionSegura();

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') .
        '">';
}
