<?php $this->layout('commons/views/layout', ['title' => 'Reloj Checador — Comandas VOSK']) ?>

<div class="glass-card">
    <h2>Reloj Checador de Personal</h2>
    <p style="color: var(--text-muted); margin: 1rem 0 2rem 0;">Registro de asistencia (entrada y salida) para empleados.</p>
    
    <div style="background: rgba(255, 255, 255, 0.02); border: 1px dashed var(--surface-border); padding: 3rem; border-radius: 8px; text-align: center; max-width: 500px; margin: 0 auto;">
        <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">⏰</span>
        <h4 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Ingrese su NIP</h4>
        
        <form hx-post="/restaurant/sistema/reloj" hx-target="#checador-feedback" hx-swap="outerHTML" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="password" name="pin" required placeholder="••••" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--surface-border); padding: 1rem; border-radius: 8px; color: var(--text-main); font-size: 1.5rem; text-align: center; letter-spacing: 0.5rem; width: 100%; max-width: 200px; margin: 0 auto;">
            
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                <button type="submit" name="tipo" value="Entrada" class="btn btn-primary" style="background: var(--success);">Registrar Entrada</button>
                <button type="submit" name="tipo" value="Salida" class="btn btn-primary" style="background: var(--accent);">Registrar Salida</button>
            </div>
        </form>
        
        <div id="checador-feedback" style="margin-top: 1.5rem;"></div>
    </div>
</div>
