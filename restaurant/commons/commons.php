<?php
// commons.php - Inicialización global de servicios, manejo de errores y dependencias

// 1. Iniciar sesión PHP con banderas de seguridad
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// 2. Cargar el cargador manual de librerías
require_once __DIR__ . '/libs/autoload.php';

use Common\DB;
use Common\Logger;
use Delight\Auth\Auth;

// 3. Manejo de Errores Global (PSR-3)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    $message = sprintf("Error [%d]: %s en %s:%d", $errno, $errstr, $errfile, $errline);
    Logger::log("ERROR", $message);
    return true;
});

set_exception_handler(function ($exception) {
    $message = sprintf(
        "Excepcion no capturada: %s en %s:%d\nTrace:\n%s",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    Logger::log("CRITICAL", $message);
    
    $config = require __DIR__ . '/config.php';
    if (($config['app']['env'] ?? 'production') === 'development') {
        echo "<h1>Internal Server Error (500)</h1><pre>" . htmlspecialchars($message) . "</pre>";
    } else {
        http_response_code(500);
        echo "<h1>Ha ocurrido un error interno.</h1><p>Por favor contacte al administrador.</p>";
    }
    exit(1);
});

// 4. Inicializar Delight Auth y registrar en Flight
try {
    $pdo = DB::connect();
    // Registrar Delight Auth directamente en Flight para DI (Dependency Injection)
    Flight::register('auth', 'Delight\Auth\Auth', [$pdo]);
} catch (\Exception $e) {
    // Si falla, se registra pero no bloquea el servidor a menos que se requiera auth
    Logger::log("CRITICAL", "Fallo al inicializar Delight Auth en commons: " . $e->getMessage());
}

// 5. Configurar e Inicializar Plates (Views Engine)
Flight::register('view', 'League\Plates\Engine', [], function($view) {
    // Carpeta raíz de vistas (permite templates como 'mesero/views/index')
    $view->setDirectory(__DIR__ . '/../');
});

// Registrar la conexión PDO para inyección sencilla en rutas de Flight
Flight::register('db', 'PDO', [], function() {
    return DB::connect();
});
