<?php
// DB.php - Clase para conexión PDO e interacción transaccional con la base de datos

namespace Common;

use PDO;
use PDOException;

class DB {
    private static ?PDO $instance = null;

    public static function connect(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/config.php';
            $dbConf = $config['db'];

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $dbConf['host'],
                $dbConf['port'],
                $dbConf['name'],
                $dbConf['charset']
            );

            try {
                self::$instance = new PDO($dsn, $dbConf['user'], $dbConf['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Registrar error en el log local de emergencia
                $logPath = $config['app']['log_path'];
                $logDir = dirname($logPath);
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                $logLine = sprintf(
                    "[%s] [FATAL] Error de conexión PDO: %s\n",
                    date('Y-m-d H:i:s'),
                    $e->getMessage()
                );
                file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
                throw $e;
            }
        }

        return self::$instance;
    }
}
