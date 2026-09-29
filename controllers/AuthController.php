<?php
declare(strict_types=1);

class AuthController
{
    public function __construct(
        private Usuario $usuarioModel
    ) {
    }

    public function autenticar(string $email, string $password): array
    {
        $email = trim(mb_strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El correo electrónico no es válido.',
            ];
        }

        if ($password === '') {
            return [
                'success' => false,
                'message' => 'La contraseña es obligatoria.',
            ];
        }

        $usuario = $this->usuarioModel->buscarPorEmail($email);

        if (!$usuario || !(bool) $usuario['estado']) {
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ];
        }

        if (!password_verify($password, $usuario['password'])) {
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ];
        }

        iniciarSesionSegura();
        session_regenerate_id(true);

        // El token CSRF anterior pertenecía a la sesión preautenticada.
        // Se fuerza su regeneración después del cambio de ID de sesión.
        unset($_SESSION['csrf_token']);

        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'email' => $usuario['email'],
            'rol_id' => (int) $usuario['rol_id'],
            'rol_nombre' => $usuario['rol_nombre'],
        ];

        $this->usuarioModel->actualizarUltimoAcceso((int) $usuario['id']);

        return [
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
        ];
    }

    public function cerrarSesion(): void
    {
        iniciarSesionSegura();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
