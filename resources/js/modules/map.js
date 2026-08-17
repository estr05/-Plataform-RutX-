import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Módulo de mapas RutX — Leaflet + OpenStreetMap vía npm/Vite (prohibido CDN).
 *
 * Inicializa automáticamente los contenedores [data-rutx-map] del DOM leyendo
 * markers/center/zoom desde data-attributes. Los marcadores son circleMarker
 * con colores semánticos de los tokens --rutx-status-* (success/warning/
 * error/unknown); el texto de los popups se inserta con textContent (sin
 * HTML) para evitar XSS con labels remotos.
 *
 * Exporta rutxMap y routeMarker para uso programático futuro.
 *
 * rutxMap(containerId) es idempotente: si el mapa ya fue creado para ese
 * id devuelve la instancia existente sin recrearla (evita el error
 * "Map container is already initialized" de Leaflet en re-renders de
 * Livewire). refreshRutxMaps() re-inicializa solo contenedores nuevos.
 */
const STATUS_TOKENS = {
    active: '--rutx-status-success',
    delayed: '--rutx-status-warning',
    stopped: '--rutx-status-error',
    unknown: '--rutx-status-unknown',
};

const DEFAULT_CENTER = [20.6, -103.4];

/**
 * Map<string, L.Map> — registra instancias Leaflet por containerId.
 * Clave: valor del atributo id del contenedor.
 * Permite devolver la instancia existente sin llamar L.map() dos veces
 * sobre el mismo div (Leaflet lanza error si el contenedor ya está
 * inicializado). Resuelve H-02: re-render de wire:poll sin error.
 */
const instances = new Map();

/**
 * Lee un token CSS desde :root (getComputedStyle).
 */
function tokenValue(token) {
    return getComputedStyle(document.documentElement).getPropertyValue(token).trim();
}

/**
 * Crea el mapa Leaflet con tiles de OpenStreetMap (sin CDN de librerías).
 * Es idempotente: si ya existe una instancia para containerId la devuelve
 * sin crear una nueva.
 *
 * @param {string} containerId  id del elemento contenedor del mapa.
 * @param {{ center?: number[], zoom?: number }} opts Opciones de vista inicial.
 * @returns {L.Map}
 */
export function rutxMap(containerId, { center = DEFAULT_CENTER, zoom = 12 } = {}) {
    // Idempotencia: devolver instancia existente para evitar
    // "Map container is already initialized" de Leaflet.
    if (instances.has(containerId)) {
        return instances.get(containerId);
    }

    const map = L.map(containerId, { zoomControl: true });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    map.setView(center, zoom);
    instances.set(containerId, map);

    return map;
}

/**
 * Marcador circleMarker con color semántico de tokens --rutx-status-*.
 */
export function routeMarker(lat, lon, { status = 'unknown', label = '' } = {}) {
    const token = STATUS_TOKENS[status] ?? STATUS_TOKENS.unknown;
    const fillColor = tokenValue(token) || tokenValue(STATUS_TOKENS.unknown);

    const marker = L.circleMarker([lat, lon], {
        radius: 8,
        color: tokenValue('--rutx-surface'),
        weight: 2,
        fillColor,
        fillOpacity: 1,
    });

    const popupContent = document.createElement('div');
    popupContent.className = 'rutx-map-popup';
    popupContent.textContent = label;

    marker.bindPopup(L.popup().setContent(popupContent));

    return marker;
}

/**
 * Lee y valida un data-attribute con JSON; ante cualquier fallo usa fallback.
 */
function safeParse(raw, fallback) {
    if (typeof raw !== 'string' || raw === '') {
        return fallback;
    }

    try {
        return JSON.parse(raw);
    } catch {
        return fallback;
    }
}

/**
 * Re-inicializa todos los contenedores [data-rutx-map] del DOM.
 * Los ya inicializados (en instances) se omiten; los nuevos se inicializan.
 *
 * @stub Polling documentado: guidelines §2.3. El re-render de Livewire crea
 *       un div nuevo con el mismo id; rutxMap() devuelve la instancia
 *       existente sin recrearla, evitando el error de Leaflet.
 */
export function refreshRutxMaps() {
    document.querySelectorAll('[data-rutx-map]').forEach((container) => {
        if (!container.id) {
            return;
        }

        try {
            const markers = safeParse(container.dataset.markers, []);
            const center  = safeParse(container.dataset.center, DEFAULT_CENTER);
            const zoom    = Number(container.dataset.zoom ?? 12);

            const map = rutxMap(container.id, { center, zoom });

            markers.forEach((marker) => {
                routeMarker(
                    Number(marker.lat),
                    Number(marker.lon),
                    { status: marker.status ?? 'unknown', label: marker.label ?? '' },
                ).addTo(map);
            });
        } catch (error) {
            console.warn('No se pudo inicializar el mapa de RutX.', error);
        }
    });
}

// ── Inicialización inicial ────────────────────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshRutxMaps, { once: true });
} else {
    refreshRutxMaps();
}

// ── Listeners para re-disparo tras polling / navegación Livewire ───────────

/**
 * Evento personalizado que Alpine/Livewire despacha cuando el DOM con mapas
 * ha sido re-renderizado por un ciclo de wire:poll.
 * En Blade: Alpine $dispatch('rutx:refresh-maps') desde x-init.
 */
document.addEventListener('rutx:refresh-maps', () => refreshRutxMaps());

/**
 * Livewire 3 dispara 'livewire:navigated' al terminar de actualizar el DOM.
 * Re-inicializar por si la nueva página contiene mapas.
 */
document.addEventListener('livewire:navigated', () => refreshRutxMaps());

export { L };
