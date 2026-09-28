<?php
declare(strict_types=1);

final class Correo
{
    public function __construct(private PDO $pdo)
    {
    }

    public function existePorMensaje(int $cuentaGmailId, string $gmailMessageId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM correos
             WHERE cuenta_gmail_id = :cuenta_gmail_id
               AND gmail_message_id = :gmail_message_id
             LIMIT 1'
        );
        $stmt->execute([
            'cuenta_gmail_id' => $cuentaGmailId,
            'gmail_message_id' => $gmailMessageId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO correos
             (cuenta_gmail_id, gmail_message_id, thread_id, remitente, destinatario,
              asunto, contenido, fecha_recepcion, estado)
             VALUES (:cuenta_gmail_id, :gmail_message_id, :thread_id, :remitente,
                     :destinatario, :asunto, :contenido, :fecha_recepcion, :estado)'
        );
        $stmt->execute($datos);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Lista correos respetando el alcance de las cuentas Gmail del usuario.
     * El Administrador puede consultar todas las cuentas activas.
     */
    public function listar(int $usuarioId, bool $esAdministrador, array $filtros, int $pagina, int $porPagina = 20): array
    {
        $porPagina = max(1, min($porPagina, 100));
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $porPagina;

        $where = [];
        $params = [];

        if ($esAdministrador) {
            $where[] = 'cg.estado = :estado_cuenta';
            $params['estado_cuenta'] = 'ACTIVA';
        } else {
            $where[] = 'EXISTS (
                SELECT 1 FROM usuario_cuenta_gmail ucg
                WHERE ucg.cuenta_gmail_id = cg.id
                  AND ucg.usuario_id = :usuario_id
            )';
            $params['usuario_id'] = $usuarioId;
        }

        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $where[] = '(c.remitente LIKE :buscar_remitente
                      OR c.destinatario LIKE :buscar_destinatario
                      OR c.asunto LIKE :buscar_asunto)';
            $like = '%' . $buscar . '%';
            $params['buscar_remitente'] = $like;
            $params['buscar_destinatario'] = $like;
            $params['buscar_asunto'] = $like;
        }

        $cuentaId = filter_var($filtros['cuenta_id'] ?? null, FILTER_VALIDATE_INT);
        if ($cuentaId && $cuentaId > 0) {
            $where[] = 'c.cuenta_gmail_id = :cuenta_id';
            $params['cuenta_id'] = $cuentaId;
        }

        $estadosPermitidos = ['NO_CLASIFICADO', 'CLASIFICADO', 'ATENDIDO', 'ARCHIVADO'];
        $estado = (string) ($filtros['estado'] ?? '');
        if (in_array($estado, $estadosPermitidos, true)) {
            $where[] = 'c.estado = :estado_correo';
            $params['estado_correo'] = $estado;
        }

        $whereSql = implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*)
                     FROM correos c
                     INNER JOIN cuentas_gmail cg ON cg.id = c.cuenta_gmail_id
                     WHERE ' . $whereSql;
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = 'SELECT c.id, c.cuenta_gmail_id, c.remitente, c.destinatario,
                       c.asunto, c.fecha_recepcion, c.estado,
                       cg.email AS cuenta_email,
                       cl.prioridad, cl.confianza
                FROM correos c
                INNER JOIN cuentas_gmail cg ON cg.id = c.cuenta_gmail_id
                LEFT JOIN clasificaciones cl ON cl.id = (
                           SELECT cl2.id FROM clasificaciones cl2
                           WHERE cl2.correo_id = c.id
                           ORDER BY cl2.id DESC LIMIT 1
                       )
                WHERE ' . $whereSql . '
                ORDER BY c.fecha_recepcion DESC, c.id DESC
                LIMIT :limite OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => max(1, (int) ceil($total / $porPagina)),
        ];
    }

    public function obtenerPorId(int $id, int $usuarioId, bool $esAdministrador): ?array
    {
        $scope = $esAdministrador
            ? 'cg.estado = :estado_cuenta'
            : 'EXISTS (
                SELECT 1 FROM usuario_cuenta_gmail ucg
                WHERE ucg.cuenta_gmail_id = cg.id
                  AND ucg.usuario_id = :usuario_id
            )';

        $sql = 'SELECT c.*, cg.email AS cuenta_email,
                       cl.id AS clasificacion_id, cl.prioridad, cl.categoria,
                       cl.confianza, cl.justificacion, cl.modelo_ia,
                       cl.fecha_clasificacion
                FROM correos c
                INNER JOIN cuentas_gmail cg ON cg.id = c.cuenta_gmail_id
                LEFT JOIN clasificaciones cl ON cl.id = (
                           SELECT cl2.id FROM clasificaciones cl2
                           WHERE cl2.correo_id = c.id
                           ORDER BY cl2.id DESC LIMIT 1
                       )
                WHERE c.id = :correo_id AND ' . $scope . '
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $params = ['correo_id' => $id];
        if ($esAdministrador) {
            $params['estado_cuenta'] = 'ACTIVA';
        } else {
            $params['usuario_id'] = $usuarioId;
        }
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function listarCuentasDisponibles(int $usuarioId, bool $esAdministrador): array
    {
        if ($esAdministrador) {
            $stmt = $this->pdo->query(
                "SELECT id, email FROM cuentas_gmail WHERE estado = 'ACTIVA' ORDER BY email"
            );
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare(
            "SELECT cg.id, cg.email
             FROM cuentas_gmail cg
             INNER JOIN usuario_cuenta_gmail ucg ON ucg.cuenta_gmail_id = cg.id
             WHERE ucg.usuario_id = :usuario_id AND cg.estado = 'ACTIVA'
             ORDER BY cg.email"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }
}
