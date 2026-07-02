<?php
// Logger.php - Helper de logs del sistema (DB y Archivo local)

namespace Common;

use DateTime;
use PDOException;

class Logger {
    public static function log(string $level, string $message, ?string $deviceId = null, ?string $correlationId = null): void {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        
        // 1. Intentar guardar en MariaDB (tabla sys_logs)
        try {
            $pdo = DB::connect();
            $stmt = $pdo->prepare("INSERT INTO `sys_logs` (`level`, `message`, `device_id`, `correlation_id`, `timestamp`) VALUES (:level, :message, :device_id, :correlation_id, :timestamp)");
            $stmt->execute([
                ':level' => $level,
                ':message' => $message,
                ':device_id' => $deviceId,
                ':correlation_id' => $correlationId,
                ':timestamp' => $timestamp
            ]);
        } catch (PDOException $e) {
            // Si la conexión a la base de datos falla, registrar el error en archivo plano
            self::logToFile("FATAL", "Fallo al escribir en sys_logs: " . $e->getMessage());
        }

        // 2. Escribir siempre en archivo local como respaldo redundante
        self::logToFile($level, $message, $deviceId, $correlationId);
    }

    private static function logToFile(string $level, string $message, ?string $deviceId = null, ?string $correlationId = null): void {
        $config = require __DIR__ . '/config.php';
        $logPath = $config['app']['log_path'];
        $logDir = dirname($logPath);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $logLine = sprintf(
            "[%s] [%s] %s %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            $deviceId ? "[Device: $deviceId]" : "",
            $correlationId ? "[Corr: $correlationId]" : ""
        );

        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
    }
}
