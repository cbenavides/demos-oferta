<?php $this->layout('commons/views/layout', ['title' => 'Cocina KDS — Comandas VOSK']) ?>

<div class="glass-card">
    <h2>Módulo de Cocina (KDS)</h2>
    <p style="color: var(--text-muted); margin: 1rem 0 2rem 0;">Visualización en tiempo real de comandas activas y comandos por voz de Cocina.</p>
    
    <div style="background: rgba(255, 255, 255, 0.02); border: 1px dashed var(--surface-border); padding: 3rem; border-radius: 8px; text-align: center; max-width: 600px; margin: 0 auto;">
        <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">🍳</span>
        <h4 style="margin-bottom: 0.5rem; font-size: 1.25rem;">Pantalla KDS Activa</h4>
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem;">
            Control de flujo server-driven. Soporta dictado local de comandos del cocinero ("preparar siguiente", "listo mesa X").
        </p>
        <button class="btn btn-primary" hx-get="/restaurant/api/cocina/estado.php" hx-target="#kds-status" hx-swap="innerHTML">Verificar Estado de Cocina</button>
        <div id="kds-status" style="margin-top: 1.5rem; font-family: monospace; color: var(--success);"></div>
    </div>
</div>
