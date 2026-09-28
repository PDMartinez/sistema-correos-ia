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
}
