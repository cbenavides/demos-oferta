<?php
// config.php - Configuración del sistema Comandas VOSK

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 6002,
        'user' => 'root',
        'pass' => 'comite_2026',
        'name' => 'vcd01',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'env' => 'development',
        'log_path' => __DIR__ . '/../logs/app.log'
    ]
];
