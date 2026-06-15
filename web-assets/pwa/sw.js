/**
 * ════════════════════════════════════════════════════════════
 * Service Worker: VOSK Comandas PWA
 * Implementa Caché de App Shell y Background Sync
 * ════════════════════════════════════════════════════════════
 */

const CACHE_NAME = 'comandas-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/web-assets/css/app-voice.css',
  '/web-assets/pwa/dexie/dexie.min.js',
  '/web-assets/pwa/dexie/db.js'
  // Nota: El modelo vosk-model-small-es-0.42.tar.gz (39MB) no se guarda en este caché HTTP
  // por defecto, para no bloquear la instalación del SW. Se precarga asíncronamente.
];

// Instalación: Pre-cacheo de la App Shell
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
        .then(cache => {
            console.log('Precaching App Shell');
            return cache.addAll(ASSETS_TO_CACHE);
        })
        .then(() => self.skipWaiting())
    );
});

// Activación: Limpieza de cachés antiguos
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(name => name !== CACHE_NAME)
                .map(name => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Estrategia Network First, fallback to Cache
self.addEventListener('fetch', event => {
    // Si la solicitud es un POST a la API (crear comanda), no intervenimos el fetch normal
    if (event.request.method === 'POST') return;

    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});

// Evento de Background Sync para enviar las comandas encoladas en Dexie.js
self.addEventListener('sync', event => {
    if (event.tag === 'sync-comandas') {
        console.log('Service Worker: Background sync disparado (sync-comandas).');
        event.waitUntil(sincronizarComandasPendientes());
    }
});

/**
 * Función que despierta y lee la BD offline para enviar por Fetch
 * (Nota: Aquí no podemos usar el import de ES6 de db.js directamente
 * porque el SW opera aislado. O usamos importScripts o re-instanciamos Dexie).
 */
async function sincronizarComandasPendientes() {
    importScripts('/web-assets/pwa/dexie/dexie.min.js');
    
    const db = new Dexie('ComandasDB');
    db.version(1).stores({ outbox_comandas: 'uuid_local, mesa_id, timestamp, sync_status' });
    
    const pendientes = await db.outbox_comandas.where('sync_status').equals('pending').toArray();
    
    if (pendientes.length === 0) return;
    
    console.log(`Encontradas ${pendientes.length} comandas para sincronizar.`);
    
    for (const comanda of pendientes) {
        try {
            const response = await fetch('/api/comandas/sincronizar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(comanda)
            });
            
            if (response.ok) {
                // Marcar como sincronizada si el servidor responde 200 OK
                await db.outbox_comandas.update(comanda.uuid_local, {sync_status: 'synced'});
            }
        } catch (err) {
            console.warn('Error sincronizando comanda (red caída aún):', err);
            // La excepción pausa la iteración o mantiene la comanda en pending
        }
    }
    
    // Purgar las ya sincronizadas
    await db.outbox_comandas.where('sync_status').equals('synced').delete();
}
