import Dexie from './dexie.min.js';

/**
 * ════════════════════════════════════════════════════════════
 * ComandasDB: Offline Persistence Layer
 * Basado en Dexie.js (Wrapper IndexedDB)
 * ════════════════════════════════════════════════════════════
 */

export const db = new Dexie('ComandasDB');

// Definición del esquema (Schema Definition)
db.version(1).stores({
  // Catálogo en caché (sincronizado al iniciar sesión) para Levenshtein
  catalog: 'id, categoria_id, nombre, precio, palabras_clave',
  
  // Cola de salida para comandas no enviadas (Offline IT1/IT2)
  outbox_comandas: 'uuid_local, mesa_id, timestamp, sync_status',
  
  // Buzón de notificaciones push y alertas (previene TTS desfasado)
  notificaciones: 'id, tipo, leido, timestamp'
});

/**
 * Registra una comanda en la cola offline.
 * Se invoca si navigator.onLine es falso, o como fallback si Fetch falla.
 */
export async function encolarComanda(mesa_id, productos, transcripcion) {
    const comanda = {
        uuid_local: crypto.randomUUID(),
        mesa_id: mesa_id,
        productos: productos, // payload JSON
        transcripcion: transcripcion,
        timestamp: new Date().getTime(),
        sync_status: 'pending'
    };
    
    await db.outbox_comandas.add(comanda);
    
    // Solicitar Background Sync al Service Worker (si está soportado)
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        const swRegistration = await navigator.serviceWorker.ready;
        try {
            await swRegistration.sync.register('sync-comandas');
            console.log("Background sync registrado para comandas.");
        } catch (e) {
            console.warn("Background sync falló, se intentará on load: ", e);
        }
    }
    
    return comanda.uuid_local;
}

/**
 * Recupera todas las comandas pendientes de envío
 */
export async function obtenerComandasPendientes() {
    return await db.outbox_comandas.where('sync_status').equals('pending').toArray();
}

/**
 * Marca una comanda como enviada exitosamente para posterior limpieza
 */
export async function confirmarComandaEnviada(uuid_local) {
    await db.outbox_comandas.update(uuid_local, {sync_status: 'synced'});
}

/**
 * Limpia las comandas ya sincronizadas
 */
export async function purgarComandasSincronizadas() {
    await db.outbox_comandas.where('sync_status').equals('synced').delete();
}
