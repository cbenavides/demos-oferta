<?php
// index.php - Front Controller principal del sistema Comandas VOSK

// 1. Cargar commons (inicializa autoloader, sesión, base de datos y Delight Auth)
require_once __DIR__ . '/commons/commons.php';

// Configurar encabezado JSON por defecto para rutas que inician con /api/
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
    header("Content-Type: application/json; charset=UTF-8");
}

// 2. Rutas de Interfaz Web (Vistas con Plates y HTMX)

// Página principal: redirigir según el rol del usuario autenticado
Flight::route('GET /', function() {
    $auth = Flight::auth();
    if (!$auth->isLoggedIn()) {
        Flight::redirect('/login');
        return;
    }
    
    // Obtener roles del usuario y redirigir al módulo correspondiente
    if ($auth->hasRole(\Delight\Auth\Role::ADMIN)) {
        Flight::redirect('/caja');
    } else {
        // Fallback básico para mesero/cocinero (simulado o según lógica interna)
        Flight::redirect('/mesero');
    }
});

// Inicio de Sesión (Login)
Flight::route('GET /login', function() {
    // Si ya está autenticado, mandar al inicio
    if (Flight::auth()->isLoggedIn()) {
        Flight::redirect('/');
        return;
    }
    echo Flight::view()->render('login/views/index');
});

Flight::route('POST /login', function() {
    $request = Flight::request();
    $email = trim($request->data->email ?? '');
    $password = trim($request->data->password ?? '');
    
    try {
        Flight::auth()->login($email, $password);
        
        if ($request->ajax()) {
            header("HX-Redirect: /restaurant/"); // Soporte para HTMX
            echo "Iniciando sesión...";
        } else {
            Flight::redirect('/');
        }
        return;
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $error = "Correo electrónico no válido.";
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $error = "Contraseña incorrecta.";
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $error = "Cuenta de correo no verificada.";
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $error = "Demasiadas peticiones. Intente más tarde.";
    }
    
    if ($request->ajax()) {
        echo "<div class='alert alert-danger'>$error</div>";
    } else {
        echo Flight::view()->render('login/views/index', ['error' => $error]);
    }
});

// Cierre de Sesión (Logout)
Flight::route('GET /logout', function() {
    if (Flight::auth()->isLoggedIn()) {
        Flight::auth()->logOut();
    }
    Flight::redirect('/login');
});

// Vistas de Módulos (Esqueleto Inicial)
Flight::route('GET /mesero', function() {
    echo Flight::view()->render('mesero/views/index');
});

Flight::route('GET /cocina', function() {
    echo Flight::view()->render('cocina/views/index');
});

Flight::route('GET /caja', function() {
    echo Flight::view()->render('caja/views/index');
});

Flight::route('GET /sistema/reloj', function() {
    echo Flight::view()->render('sistema/views/reloj');
});


// 3. API Endpoints (JSON)

// POST /restaurant/api/comanda.php — Registrar nueva comanda
Flight::route('POST /api/comanda.php', function() {
    $request = Flight::request();
    $data = json_decode($request->getBody(), true) ?? [];
    
    if (empty($data['mesa_id']) || empty($data['productos'])) {
        Flight::json(['status' => 'error', 'mensaje' => 'Parametros incompletos: se requiere mesa_id y productos'], 400);
        return;
    }
    
    // Simular registro exitoso
    Flight::json([
        'status' => 'success',
        'comanda_id' => rand(1000, 9999),
        'total' => 145.00,
        'hora_registro' => date('Y-m-d H:i:s'),
        'tts_mensaje' => 'Nueva orden recibida.',
        'tts_mesero' => 'Comanda enviada.'
    ]);
});

// GET /restaurant/api/comandas/pendientes.php — Listar comandas pendientes
Flight::route('GET /api/comandas/pendientes.php', function() {
    Flight::json([
        'status' => 'success',
        'total' => 0,
        'comandas' => []
    ]);
});

// POST /restaurant/api/cocina/comando.php — Procesar comando de voz del cocinero
Flight::route('POST /api/cocina/comando.php', function() {
    $request = Flight::request();
    $data = json_decode($request->getBody(), true) ?? [];
    
    Flight::json([
        'status' => 'success',
        'accion' => 'desconocido',
        'tts_respuesta' => 'Comando de voz recibido en cocina.'
    ]);
});

// GET /restaurant/api/cocina/estado.php — Estado general de la cocina
Flight::route('GET /api/cocina/estado.php', function() {
    Flight::json([
        'status' => 'success',
        'pendientes' => 0,
        'en_preparacion' => 0,
        'listas' => 0,
        'cancelaciones_pendientes' => 0,
        'tts_resumen' => 'No hay comandas activas.'
    ]);
});

// POST /restaurant/api/cancelacion/solicitar.php — Solicitar cancelación (mesero)
Flight::route('POST /api/cancelacion/solicitar.php', function() {
    Flight::json([
        'status' => 'success',
        'solicitud_id' => rand(1, 999),
        'estado' => 'pendiente_cocinero',
        'tts_mesero' => 'Cancelacion solicitada a cocina.',
        'tts_cocinero' => 'Nueva solicitud de cancelacion.'
    ]);
});

// POST /restaurant/api/cancelacion/responder.php — Responder cancelación (cocinero)
Flight::route('POST /api/cancelacion/responder.php', function() {
    Flight::json([
        'status' => 'success',
        'accion' => 'aprobada',
        'tts_cocinero' => 'Cancelacion aprobada.',
        'tts_mesero' => 'Cancelacion aprobada por cocina.'
    ]);
});

// POST /restaurant/api/cuenta/cerrar.php — Cerrar cuenta de mesa
Flight::route('POST /api/cuenta/cerrar.php', function() {
    Flight::json([
        'status' => 'success',
        'message' => 'Cuenta cerrada',
        'ticket_url' => '/restaurant/api/cuenta/imprimir_ticket.php?mesa=5'
    ]);
});

// POST /restaurant/api/telemetria/ingesta.php — Ingesta de ráfagas de Logs (PWA offline)
Flight::route('POST /api/telemetria/ingesta.php', function() {
    $request = Flight::request();
    $data = json_decode($request->getBody(), true) ?? [];
    
    Flight::json([
        'status' => 'success',
        'inserted_count' => count($data['logs'] ?? []),
        'hash_catalogo_actual' => 'a8b9f12c'
    ]);
});

// 4. Iniciar enrutador
Flight::start();
