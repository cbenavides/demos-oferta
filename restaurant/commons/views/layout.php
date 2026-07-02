<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? 'Comandas VOSK') ?></title>
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Estilos de Diseño Glassmorphic Premium -->
    <style>
        :root {
            --bg-color: #0b0f19;
            --surface-color: rgba(255, 255, 255, 0.03);
            --surface-border: rgba(255, 255, 255, 0.08);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #f43f5e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-blur: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(244, 63, 94, 0.04) 0%, transparent 40%);
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        /* Barra de Navegación Premium */
        header.main-nav {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border-bottom: 1px solid var(--surface-border);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #a5b4fc, #f43f5e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            background: rgba(244, 63, 94, 0.15);
            color: var(--accent);
            border: 1px solid rgba(244, 63, 94, 0.3);
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }

        nav.menu a {
            color: var(--text-muted);
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        nav.menu a:hover, nav.menu a.active {
            color: var(--text-main);
        }

        .logout-btn {
            color: var(--accent) !important;
        }

        /* Contenido Principal */
        main {
            flex: 1;
            padding: 2rem;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Tarjeta de Cristal (Glassmorphic) */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--surface-border);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        footer {
            padding: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-muted);
            border-top: 1px solid var(--surface-border);
            background: rgba(15, 23, 42, 0.3);
        }

        /* Utilidades Generales */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            border: none;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            font-size: 0.95rem;
        }

        .alert-danger {
            background: rgba(244, 63, 94, 0.15);
            color: #fda4af;
            border-color: rgba(244, 63, 94, 0.3);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border-color: rgba(16, 185, 129, 0.3);
        }
    </style>

    <!-- Cargador Local Offline de HTMX -->
    <script src="/restaurant/web-assets/libs/htmx.min.js"></script>
</head>
<body>
    <header class="main-nav">
        <div class="logo">
            VOSK 🍔 <span>LAN OFFLINE</span>
        </div>
        <nav class="menu">
            <?php if (Flight::auth()->isLoggedIn()): ?>
                <a href="/restaurant/mesero">Mesero</a>
                <a href="/restaurant/cocina">Cocina KDS</a>
                <a href="/restaurant/caja">Caja</a>
                <a href="/restaurant/sistema/reloj">Reloj</a>
                <a href="/restaurant/logout" class="logout-btn">Salir</a>
            <?php else: ?>
                <a href="/restaurant/login">Ingresar</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?= $this->section('content') ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Comandas VOSK — Desarrollado de forma Frugal y Offline (LAN)</p>
    </footer>
</body>
</html>
