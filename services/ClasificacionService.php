<?php
declare(strict_types=1);

final class ClasificacionService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function clasificarCorreo(int $correoId, int $usuarioId, bool $esAdministrador): array
    {
        $correoModel = new Correo($this->pdo);
        $correo = $correoModel->obtenerPorId($correoId, $usuarioId, $esAdministrador);
        if (!$correo) {
            throw new RuntimeException('Correo no encontrado o no autorizado.');
        }

        $ia = new OpenAIClassificationService();
        $resultado = $ia->clasificar($correo);

        $stmt = $this->pdo->prepare(
            'INSERT INTO clasificaciones
             (correo_id, prioridad, categoria, confianza, justificacion, modelo_ia)
             VALUES (:correo_id, :prioridad, :categoria, :confianza, :justificacion, :modelo_ia)'
        );
        $stmt->execute([
            'correo_id' => $correoId,
            'prioridad' => $resultado['prioridad'],
            'categoria' => $resultado['categoria'],
            'confianza' => $resultado['confianza'],
            'justificacion' => $resultado['justificacion'],
            'modelo_ia' => $resultado['modelo_ia'],
        ]);

        $clasificacionId = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare(
            "UPDATE correos SET estado = 'CLASIFICADO' WHERE id = :correo_id"
        );
        $stmt->execute(['correo_id' => $correoId]);

        return $resultado + ['clasificacion_id' => $clasificacionId];
    }

    public function clasificarPendientes(int $usuarioId, bool $esAdministrador, int $limite = 10): array
    {
        $limite = max(1, min($limite, 50));
        $correoModel = new Correo($this->pdo);
        $filtros = ['estado' => 'NO_CLASIFICADO'];
        $listado = $correoModel->listar($usuarioId, $esAdministrador, $filtros, 1, $limite);

        $ia = new OpenAIClassificationService();
        if (!$ia->estaConfigurado()) {
            throw new RuntimeException('La API de IA no está configurada.');
        }

        $importados = 0;
        $errores = [];
        foreach ($listado['items'] as $correo) {
            try {
                $this->clasificarCorreo((int) $correo['id'], $usuarioId, $esAdministrador);
                $importados++;
            } catch (Throwable $e) {
                $errores[] = [
                    'correo_id' => (int) $correo['id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'procesados' => count($listado['items']),
            'clasificados' => $importados,
            'errores' => $errores,
        ];
    }
}
