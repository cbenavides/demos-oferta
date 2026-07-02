<?php $this->layout('commons/views/layout', ['title' => 'Caja y Arqueo — Comandas VOSK']) ?>

<div class="glass-card">
    <h2>Módulo de Caja y Finanzas</h2>
    <p style="color: var(--text-muted); margin: 1rem 0 2rem 0;">Cierres de cuenta, facturación y conciliación diaria (Cortes X y Z).</p>
    
    <div style="background: rgba(255, 255, 255, 0.02); border: 1px dashed var(--surface-border); padding: 3rem; border-radius: 8px; text-align: center; max-width: 600px; margin: 0 auto;">
        <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">💸</span>
        <h4 style="margin-bottom: 0.5rem; font-size: 1.25rem;">Terminal de Ventas</h4>
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem;">
            Panel de control para arqueo, consulta de tickets históricos, impresión CUPS local y visualización del desempeño general de meseros.
        </p>
        <button class="btn btn-primary" onclick="alert('Generando reporte preliminar...')">Ver Ventas de Hoy</button>
    </div>
</div>
