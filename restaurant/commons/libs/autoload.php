<?php
// autoload.php - Autoloader para el entorno frugal Comandas VOSK

// 1. Inicializar el autoloteador nativo de Flight PHP
require_once __DIR__ . '/flight/autoload.php';

// 2. Registrar cargador PSR-4 para Plates y Delight-Auth
spl_autoload_register(function ($class) {
    $prefixes = [
        'League\\Plates\\' => __DIR__ . '/plates/',
        'Delight\\Auth\\' => __DIR__ . '/auth/Delight/Auth/',
        'Delight\\Cookie\\' => __DIR__ . '/auth/Delight/Cookie/',
        'Delight\\Db\\' => __DIR__ . '/auth/Delight/Db/',
        'Delight\\Base64\\' => __DIR__ . '/auth/Delight/Base64/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
