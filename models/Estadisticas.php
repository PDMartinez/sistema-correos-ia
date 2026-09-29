<?php
declare(strict_types=1);

final class Estadisticas
{
    public function __construct(private PDO $pdo)
    {
    }

    private function construirFiltros(int $usuarioId, bool $esAdministrador, array $filtros): array
    {
        $where = [];
        $params = [];

        if ($esAdministrador) {
            $where[] = "cg.estado = :estado_cuenta_scope";
            $params['estado_cuenta_scope'] = 'ACTIVA';
        } else {
            $where[] = "EXISTS (
                SELECT 1 FROM usuario_cuenta_gmail ucg
                WHERE ucg.cuenta_gmail_id = cg.id
                  AND ucg.usuario_id = :usuario_id_scope
            )";
            $params['usuario_id_scope'] = $usuarioId;
        }

        $cuentaId = filter_var($filtros['cuenta_id'] ?? null, FILTER_VALIDATE_INT);
        if ($cuentaId !== false && $cuentaId !== null && $cuentaId > 0) {
            $where[] = 'c.cuenta_gmail_id = :cuenta_id';
            $params['cuenta_id'] = $cuentaId;
        }

        $fechaDesde = trim((string) ($filtros['fecha_desde'] ?? ''));
        if ($this->esFechaValida($fechaDesde)) {
            $where[] = 'c.fecha_recepcion >= :fecha_desde';
            $params['fecha_desde'] = $fechaDesde . ' 00:00:00';
        }

        $fechaHasta = trim((string) ($filtros['fecha_hasta'] ?? ''));
        if ($this->esFechaValida($fechaHasta)) {
            $where[] = 'c.fecha_recepcion < DATE_ADD(:fecha_hasta, INTERVAL 1 DAY)';
            $params['fecha_hasta'] = $fechaHasta;
        }

        $prioridad = (string) ($filtros['prioridad'] ?? '');
        if (in_array($prioridad, ['ALTA', 'MEDIA', 'BAJA'], true)) {
            $where[] = 'cl.prioridad = :prioridad';
            $params['prioridad'] = $prioridad;
        }

        $categorias = ['URGENTE', 'FINANCIERO', 'ADMINISTRATIVO', 'OPERATIVO', 'INFORMACION', 'OTRO'];
        $categoria = trim((string) ($filtros['categoria'] ?? ''));
        if (in_array($categoria, $categorias, true)) {
            $where[] = 'cl.categoria = :categoria';
            $params['categoria'] = $categoria;
        }

        return [implode(' AND ', $where), $params];
    }

    private function baseSql(string $where): string
    {
        return "FROM correos c
                INNER JOIN cuentas_gmail cg ON cg.id = c.cuenta_gmail_id
                LEFT JOIN clasificaciones cl ON cl.id = (
                    SELECT cl2.id FROM clasificaciones cl2
                    WHERE cl2.correo_id = c.id
                    ORDER BY cl2.id DESC LIMIT 1
                )
                LEFT JOIN revisiones_clasificacion rv ON rv.id = (
                    SELECT rv2.id FROM revisiones_clasificacion rv2
                    WHERE rv2.clasificacion_id = cl.id
                    ORDER BY rv2.id DESC LIMIT 1
                )
                WHERE {$where}";
    }

    private function ejecutar(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function resumen(int $usuarioId, bool $esAdministrador, array $filtros): array
    {
        [$where, $params] = $this->construirFiltros($usuarioId, $esAdministrador, $filtros);
        $base = $this->baseSql($where);

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN cl.id IS NULL THEN 1 ELSE 0 END) AS no_clasificados,
                    SUM(CASE WHEN cl.id IS NOT NULL THEN 1 ELSE 0 END) AS clasificados,
                    SUM(CASE WHEN cl.id IS NOT NULL AND rv.id IS NULL THEN 1 ELSE 0 END) AS pendientes_revision,
                    SUM(CASE WHEN rv.id IS NOT NULL THEN 1 ELSE 0 END) AS revisados,
                    SUM(CASE WHEN c.estado = 'ATENDIDO' THEN 1 ELSE 0 END) AS atendidos,
                    SUM(CASE WHEN c.estado = 'ARCHIVADO' THEN 1 ELSE 0 END) AS archivados,
                    AVG(CASE WHEN cl.id IS NOT NULL THEN cl.confianza ELSE NULL END) AS confianza_promedio,
                    SUM(CASE WHEN rv.id IS NOT NULL AND cl.prioridad = rv.prioridad_final THEN 1 ELSE 0 END) AS coincidencias,
                    SUM(CASE WHEN rv.id IS NOT NULL AND cl.prioridad <> rv.prioridad_final THEN 1 ELSE 0 END) AS modificaciones
                {$base}";

        $fila = $this->ejecutar($sql, $params)->fetch() ?: [];
        $revisados = (int) ($fila['revisados'] ?? 0);
        $coincidencias = (int) ($fila['coincidencias'] ?? 0);

        $fila['concordancia'] = $revisados > 0 ? ($coincidencias / $revisados) : null;
        $fila['confianza_promedio'] = $fila['confianza_promedio'] !== null
            ? (float) $fila['confianza_promedio']
            : null;

        foreach (['total','no_clasificados','clasificados','pendientes_revision','revisados','atendidos','archivados','coincidencias','modificaciones'] as $campo) {
            $fila[$campo] = (int) ($fila[$campo] ?? 0);
        }

        return $fila;
    }

    public function porPrioridad(int $usuarioId, bool $esAdministrador, array $filtros): array
    {
        [$where, $params] = $this->construirFiltros($usuarioId, $esAdministrador, $filtros);
        $base = $this->baseSql($where);
        $sql = "SELECT cl.prioridad, COUNT(*) AS cantidad
                {$base}
                AND cl.id IS NOT NULL
                GROUP BY cl.prioridad
                ORDER BY FIELD(cl.prioridad, 'ALTA','MEDIA','BAJA')";
        return $this->ejecutar($sql, $params)->fetchAll();
    }

    public function porCategoria(int $usuarioId, bool $esAdministrador, array $filtros): array
    {
        [$where, $params] = $this->construirFiltros($usuarioId, $esAdministrador, $filtros);
        $base = $this->baseSql($where);
        $sql = "SELECT COALESCE(cl.categoria, 'SIN CATEGORIA') AS categoria, COUNT(*) AS cantidad
                {$base}
                AND cl.id IS NOT NULL
                GROUP BY cl.categoria
                ORDER BY cantidad DESC, categoria ASC";
        return $this->ejecutar($sql, $params)->fetchAll();
    }

    public function porConfianza(int $usuarioId, bool $esAdministrador, array $filtros): array
    {
        [$where, $params] = $this->construirFiltros($usuarioId, $esAdministrador, $filtros);
        $base = $this->baseSql($where);
        $sql = "SELECT
                    SUM(CASE WHEN cl.confianza >= 0.80 THEN 1 ELSE 0 END) AS alta,
                    SUM(CASE WHEN cl.confianza >= 0.50 AND cl.confianza < 0.80 THEN 1 ELSE 0 END) AS media,
                    SUM(CASE WHEN cl.confianza < 0.50 THEN 1 ELSE 0 END) AS baja
                {$base}
                AND cl.id IS NOT NULL AND cl.confianza IS NOT NULL";
        $fila = $this->ejecutar($sql, $params)->fetch() ?: [];
        return [
            'alta' => (int) ($fila['alta'] ?? 0),
            'media' => (int) ($fila['media'] ?? 0),
            'baja' => (int) ($fila['baja'] ?? 0),
        ];
    }

    public function evolucion(int $usuarioId, bool $esAdministrador, array $filtros): array
    {
        [$where, $params] = $this->construirFiltros($usuarioId, $esAdministrador, $filtros);
        $base = $this->baseSql($where);
        $sql = "SELECT DATE(c.fecha_recepcion) AS fecha,
                       COUNT(*) AS total,
                       SUM(CASE WHEN cl.id IS NOT NULL THEN 1 ELSE 0 END) AS clasificados,
                       SUM(CASE WHEN rv.id IS NOT NULL THEN 1 ELSE 0 END) AS revisados
                {$base}
                GROUP BY DATE(c.fecha_recepcion)
                ORDER BY fecha ASC";
        return $this->ejecutar($sql, $params)->fetchAll();
    }

    public function listarCuentasDisponibles(int $usuarioId, bool $esAdministrador): array
    {
        if ($esAdministrador) {
            $stmt = $this->pdo->query("SELECT id, email FROM cuentas_gmail WHERE estado = 'ACTIVA' ORDER BY email");
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

    private function esFechaValida(string $fecha): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }
}
