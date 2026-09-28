<?php
declare(strict_types=1);

class UsuarioController
{
    public function __construct(private PDO $pdo, private Usuario $model) {}

    public function guardar(array $post, int $actorId): array
    {
        $id = filter_var($post['id'] ?? '0', FILTER_VALIDATE_INT);
        $id = ($id !== false && $id > 0) ? $id : null;
        $nombre = trim((string) ($post['nombre'] ?? ''));
        $apellido = trim((string) ($post['apellido'] ?? ''));
        $email = mb_strtolower(trim((string) ($post['email'] ?? '')));
        $rolId = filter_var($post['rol_id'] ?? null, FILTER_VALIDATE_INT);
        $password = (string) ($post['password'] ?? '');
        $confirmacion = (string) ($post['password_confirm'] ?? '');

        if ($nombre === '' || mb_strlen($nombre) > 100 ||
            $apellido === '' || mb_strlen($apellido) > 100) {
            return ['ok' => false, 'mensaje' => 'Nombre y apellido son obligatorios (máximo 100 caracteres).'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            return ['ok' => false, 'mensaje' => 'Introduce un correo electrónico válido.'];
        }
        if (!$rolId || !$this->model->rolActivo((int) $rolId)) {
            return ['ok' => false, 'mensaje' => 'Selecciona un rol activo.'];
        }
        if ($id === null && $password === '') {
            return ['ok' => false, 'mensaje' => 'La contraseña es obligatoria para un usuario nuevo.'];
        }
        if ($password !== '' && (strlen($password) < 12 || strlen($password) > 72)) {
            return ['ok' => false, 'mensaje' => 'La contraseña debe tener entre 12 y 72 caracteres.'];
        }
        if ($password !== $confirmacion) {
            return ['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }

        try {
            $this->pdo->beginTransaction();
            $this->model->bloquearAdministradoresActivos();
            $anterior = $id === null ? null : $this->model->obtenerPorId($id);
            if ($id !== null && !$anterior) {
                throw new DomainException('El usuario solicitado no existe.');
            }
            if ($id === $actorId && $anterior && (int) $anterior['rol_id'] !== (int) $rolId) {
                throw new DomainException('No puedes cambiar tu propio rol.');
            }
            if ($this->model->emailExiste($email, $id)) {
                throw new DomainException('Ya existe un usuario con ese correo electrónico.');
            }
            if ($anterior && (int) $anterior['estado'] === 1 &&
                $this->model->esAdministrador($anterior) &&
                (int) $anterior['rol_id'] !== (int) $rolId &&
                $this->model->contarAdministradoresActivos() <= 1) {
                throw new DomainException('No puedes cambiar el rol del último administrador activo.');
            }

            $datos = [
                'rol_id' => (int) $rolId,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
            ];
            if ($password !== '') {
                $datos['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($id === null) {
                $id = $this->model->crear($datos);
                $accion = 'CREAR_USUARIO';
            } else {
                $this->model->actualizar($id, $datos);
                $accion = 'EDITAR_USUARIO';
            }
            $this->auditar($actorId, $accion, $id);
            $this->pdo->commit();
            return ['ok' => true, 'mensaje' => 'Usuario guardado correctamente.'];
        } catch (DomainException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('Error al guardar usuario: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo guardar el usuario.'];
        }
    }

    public function cambiarEstado(int $id, int $estado, int $actorId): array
    {
        if ($id === $actorId) {
            return ['ok' => false, 'mensaje' => 'No puedes cambiar el estado de tu propia cuenta.'];
        }
        if (!in_array($estado, [0, 1], true)) {
            return ['ok' => false, 'mensaje' => 'Estado inválido.'];
        }
        try {
            $this->pdo->beginTransaction();
            $this->model->bloquearAdministradoresActivos();
            $usuario = $this->model->obtenerPorId($id);
            if (!$usuario) {
                throw new DomainException('El usuario no existe.');
            }
            if ($estado === 0 && (int) $usuario['estado'] === 1 &&
                $this->model->esAdministrador($usuario) &&
                $this->model->contarAdministradoresActivos() <= 1) {
                throw new DomainException('No puedes desactivar el último administrador activo.');
            }
            $this->model->cambiarEstado($id, $estado);
            $this->auditar($actorId, $estado === 1 ? 'ACTIVAR_USUARIO' : 'DESACTIVAR_USUARIO', $id);
            $this->pdo->commit();
            return ['ok' => true, 'mensaje' => 'Estado actualizado correctamente.'];
        } catch (DomainException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('Error al cambiar estado: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo cambiar el estado.'];
        }
    }

    private function auditar(int $actorId, string $accion, int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auditoria (usuario_id, accion, modulo, descripcion, ip, user_agent)
             VALUES (:actor, :accion, :modulo, :descripcion, :ip, :agente)'
        );
        $stmt->execute([
            'actor' => $actorId,
            'accion' => $accion,
            'modulo' => 'usuarios',
            'descripcion' => 'Usuario afectado ID: ' . $usuarioId,
            'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'agente' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
        ]);
    }
}
