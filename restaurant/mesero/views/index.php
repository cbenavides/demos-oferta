<?php $this->layout('commons/views/layout', ['title' => 'Mesero PWA — Comandas VOSK']) ?>

<div class="glass-card">
    <h2>Módulo de Mesero (PWA)</h2>
    <p style="color: var(--text-muted); margin: 1rem 0 2rem 0;">Escucha de voz local (VOSK) y base de datos local (Dexie.js).</p>
    
    <div style="background: rgba(255, 255, 255, 0.02); border: 1px dashed var(--surface-border); padding: 3rem; border-radius: 8px; text-align: center; max-width: 600px; margin: 0 auto;">
        <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">🎙️</span>
        <h4 style="margin-bottom: 0.5rem; font-size: 1.25rem;">Reconocimiento de Voz Preparado</h4>
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem;">
            El Service Worker e IndexedDB (Dexie) están cargados. Este módulo opera 100% sin conexión cuando se pierde el enlace con el servidor LAN.
        </p>
        <button class="btn btn-primary" onclick="alert('Iniciando grabación local VOSK...')">Comenzar Dictado</button>
    </div>
</div>
