<?php $this->layout('commons/views/layout', ['title' => 'Ingreso — Comandas VOSK']) ?>

<style>
    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
    }
    
    .login-box {
        width: 100%;
        max-width: 420px;
    }

    .form-group {
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-group label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-muted);
    }

    .form-group input {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--surface-border);
        padding: 0.8rem 1rem;
        border-radius: 8px;
        color: var(--text-main);
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.2s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
    }

    .login-title {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 2rem;
        background: linear-gradient(135deg, #fff, var(--text-muted));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .btn-submit {
        width: 100%;
        margin-top: 1rem;
        padding: 0.8rem;
    }
</style>

<div class="login-container">
    <div class="login-box glass-card">
        <h2 class="login-title">Iniciar Sesión</h2>
        
        <div id="login-feedback">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $this->e($error) ?></div>
            <?php endif; ?>
        </div>

        <form hx-post="/restaurant/login" hx-target="#login-feedback" hx-swap="innerHTML">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required placeholder="admin@restaurante.local">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-submit">
                Entrar al Sistema
            </button>
        </form>
    </div>
</div>
