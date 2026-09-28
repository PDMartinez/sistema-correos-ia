<?php
declare(strict_types=1);

final class RevisionClasificacion
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(int $usuarioId, bool $esAdministrador, array $filtros, int $pagina, int $porPagina = 20): array
    {
        $porPagina = max(1, min($porPagina, 100));
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $porPagina;

        $where = [];
        $params = [];

        $scope = $esAdministrador
            ? 'cg.estado = :estado_cuenta'
            : 'EXISTS (
                SELECT 1 FROM usuario_cuenta_gmail ucg
                WHERE ucg.cuenta_gmail_id = cg.id
                  AND ucg.usuario_id = :usuario_id
            )';

        $where[] = $scope;
        if ($esAdministrador) {
            $params['estado_cuenta'] = 'ACTIVA';
        } else {
            $params['usuario_id'] = $usuarioId;
        }

        $estadoRevision = (string) ($filtros['revision'] ?? 'PENDIENTE');
        if ($estadoRevision === 'PENDIENTE') {
            $where[] = 'rv.id IS NULL';
        } elseif ($estadoRevision === 'REVISADO') {
            $where[] = 'rv.id IS NOT NULL';
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

        $prioridad = (string) ($filtros['prioridad'] ?? '');
        if (in_array($prioridad, ['ALTA', 'MEDIA', 'BAJA'], true)) {
            $where[] = 'cl.prioridad = :prioridad';
            $params['prioridad'] = $prioridad;
        }

        $categoria = trim((string) ($filtros['categoria'] ?? ''));
        $categorias = ['URGENTE', 'FINANCIERO', 'ADMINISTRATIVO', 'OPERATIVO', 'INFORMACION', 'OTRO'];
        if (in_array($categoria, $categorias, true)) {
            $where[] = 'cl.categoria = :categoria';
            $params['categoria'] = $categoria;
        }

        $confianzaMin = filter_var($filtros['confianza_min'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($confianzaMin !== false && $confianzaMin !== null && $confianzaMin >= 0 && $confianzaMin <= 1) {
            $where[] = 'cl.confianza >= :confianza_min';
            $params['confianza_min'] = $confianzaMin;
        }

        $whereSql = implode(' AND ', $where);
        $base = 'FROM correos c
                 INNER JOIN cuentas_gmail cg ON cg.id = c.cuenta_gmail_id
                 INNER JOIN clasificaciones cl ON cl.id = (
                     SELECT cl2.id FROM clasificaciones cl2
                     WHERE cl2.correo_id = c.id
                     ORDER BY cl2.id DESC LIMIT 1
                 )
                 LEFT JOIN revisiones_clasificacion rv ON rv.id = (
                     SELECT rv2.id FROM revisiones_clasificacion rv2
                     WHERE rv2.clasificacion_id = cl.id
                     ORDER BY rv2.id DESC LIMIT 1
                 )
                 WHERE ' . $whereSql;

        $stmt = $this->pdo->prepare('SELECT COUNT(*) ' . $base);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = 'SELECT c.id, c.remitente, c.destinatario, c.asunto, c.fecha_recepcion,
                       c.estado, cg.email AS cuenta_email,
                       cl.id AS clasificacion_id, cl.prioridad, cl.categoria,
                       cl.confianza, cl.justificacion, cl.modelo_ia,
                       cl.fecha_clasificacion,
                       rv.id AS revision_id, rv.prioridad_final,
                       rv.comentario AS revision_comentario,
                       rv.fecha_revision
                ' . $base . '
                ORDER BY CASE WHEN rv.id IS NULL THEN 0 ELSE 1 END,
                         cl.fecha_clasificacion DESC, c.id DESC
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

    public function obtenerParaRevision(int $correoId, int $usuarioId, bool $esAdministrador): ?array
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
                       cl.fecha_clasificacion,
                       rv.id AS revision_id, rv.usuario_id AS revisor_id,
                       rv.prioridad_anterior, rv.prioridad_final,
                       rv.comentario AS revision_comentario, rv.fecha_revision,
                       u.nombre AS revisor_nombre, u.apellido AS revisor_apellido
                FROM correos c
                INNER JOIN cuentas_gmail cg ON cg.id = c.cuenta_gmail_id
                INNER JOIN clasificaciones cl ON cl.id = (
                    SELECT cl2.id FROM clasificaciones cl2
                    WHERE cl2.correo_id = c.id
                    ORDER BY cl2.id DESC LIMIT 1
                )
                LEFT JOIN revisiones_clasificacion rv ON rv.id = (
                    SELECT rv2.id FROM revisiones_clasificacion rv2
                    WHERE rv2.clasificacion_id = cl.id
                    ORDER BY rv2.id DESC LIMIT 1
                )
                LEFT JOIN usuarios u ON u.id = rv.usuario_id
                WHERE c.id = :correo_id AND ' . $scope . '
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $params = ['correo_id' => $correoId];
        if ($esAdministrador) {
            $params['estado_cuenta'] = 'ACTIVA';
        } else {
            $params['usuario_id'] = $usuarioId;
        }
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function historial(int $clasificacionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rv.*, u.nombre, u.apellido, u.email
             FROM revisiones_clasificacion rv
             INNER JOIN usuarios u ON u.id = rv.usuario_id
             WHERE rv.clasificacion_id = :clasificacion_id
             ORDER BY rv.id DESC'
        );
        $stmt->execute(['clasificacion_id' => $clasificacionId]);
        return $stmt->fetchAll();
    }

    public function guardar(int $clasificacionId, int $usuarioId, string $prioridadFinal, ?string $comentario): array
    {
        if (!in_array($prioridadFinal, ['ALTA', 'MEDIA', 'BAJA'], true)) {
            throw new InvalidArgumentException('La prioridad final no es válida.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT prioridad, correo_id FROM clasificaciones WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $clasificacionId]);
        $clasificacion = $stmt->fetch();
        if (!$clasificacion) {
            throw new RuntimeException('Clasificación no encontrada.');
        }

        // Una clasificación solo puede tener una revisión vigente.
        // Si ya fue revisada, se actualiza esa misma revisión en lugar de crear otra.
        $stmt = $this->pdo->prepare(
            'SELECT id FROM revisiones_clasificacion
             WHERE clasificacion_id = :clasificacion_id
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['clasificacion_id' => $clasificacionId]);
        $revisionExistente = $stmt->fetchColumn();

        if ($revisionExistente !== false) {
            $revisionId = (int) $revisionExistente;
            $stmt = $this->pdo->prepare(
                'UPDATE revisiones_clasificacion
                 SET usuario_id = :usuario_id,
                     prioridad_final = :prioridad_final,
                     comentario = :comentario,
                     fecha_revision = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'prioridad_final' => $prioridadFinal,
                'comentario' => $comentario,
                'id' => $revisionId,
            ]);
            $accion = 'MODIFICAR';
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO revisiones_clasificacion
                 (clasificacion_id, usuario_id, prioridad_anterior, prioridad_final, comentario)
                 VALUES (:clasificacion_id, :usuario_id, :prioridad_anterior, :prioridad_final, :comentario)'
            );
            $stmt->execute([
                'clasificacion_id' => $clasificacionId,
                'usuario_id' => $usuarioId,
                'prioridad_anterior' => $clasificacion['prioridad'],
                'prioridad_final' => $prioridadFinal,
                'comentario' => $comentario,
            ]);
            $revisionId = (int) $this->pdo->lastInsertId();
            $accion = 'REGISTRAR';
        }

        // Una revisión humana válida deja el correo como atendido.
        $stmt = $this->pdo->prepare(
            "UPDATE correos SET estado = 'ATENDIDO' WHERE id = :correo_id"
        );
        $stmt->execute(['correo_id' => (int) $clasificacion['correo_id']]);

        return [
            'id' => $revisionId,
            'accion' => $accion,
        ];
    }
}
