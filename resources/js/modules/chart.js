import { Chart } from 'chart.js';

/**
 * Módulo de gráficas RutX — Chart.js vía npm/Vite (prohibido CDN).
 *
 * Inicializa automáticamente los <canvas data-rutx-chart> del DOM leyendo
 * type/labels/datasets desde data-attributes (los componentes Blade son solo
 * HTML). La paleta se lee en runtime de los tokens CSS --rutx-chart-* vía
 * getComputedStyle; prohibido definir colores de serie fuera de chartBlue /
 * chartCyan / secondary (guidelines §5.2).
 *
 * Exporta rutxChartOptions y buildLineDataset para uso programático futuro
 * (p. ej. desde componentes Livewire).
 */
const CHART_COLOR_TOKENS = ['--rutx-chart-blue', '--rutx-chart-cyan'];

/**
 * WeakMap<HTMLCanvasElement, Chart> — rastrea instancias activas.
 * Permite destruir la instancia previa antes de re-crear sobre el mismo
 * nodo (idempotencia en re-renders de Livewire). WeakMap evita retener
 * referencias a nodos eliminados del DOM (sin memory leak).
 */
const chartInstances = new WeakMap();

/**
 * Opciones base de Chart.js con la paleta centralizada de tokens.css.
 */
export function rutxChartOptions() {
    const palette = getComputedStyle(document.documentElement);
    const text = palette.getPropertyValue('--rutx-text').trim();
    const textMuted = palette.getPropertyValue('--rutx-text-muted').trim();
    const primary = palette.getPropertyValue('--rutx-primary').trim();

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: text, font: { family: 'Inter' } },
            },
            tooltip: {
                backgroundColor: primary,
            },
        },
        scales: {
            x: { ticks: { color: textMuted } },
            y: { ticks: { color: textMuted } },
        },
    };
}

/**
 * Serializa un dataset del contrato (SalesSeriesResponse) a Chart.js.
 *
 * @param {Array}  series     Puntos: [valor, ...] o { points: [...] }.
 * @param {string} label      Etiqueta de la serie para la leyenda.
 * @param {string} colorToken Token CSS de la serie (--rutx-chart-*).
 */
export function buildLineDataset(series, label, colorToken = '--rutx-chart-blue') {
    const palette = getComputedStyle(document.documentElement);

    return {
        label,
        data: series.points ?? series,
        borderColor: palette.getPropertyValue(colorToken).trim(),
        backgroundColor: palette.getPropertyValue(`${colorToken}-bg`).trim(),
        fill: false,
        tension: 0,
    };
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
 * Re-inicializa todas las gráficas dentro de root.
 * Destruye la instancia Chart existente (vía WeakMap) antes de crear
 * una nueva — idempotencia ante re-renders de wire:poll.
 *
 * @param {Document|HTMLElement} root Raíz de búsqueda (document por defecto).
 */
export function refreshRutxCharts(root = document) {
    root.querySelectorAll('[data-rutx-chart]').forEach((canvas) => {
        // Destruir instancia previa si el canvas ya fue inicializado.
        if (chartInstances.has(canvas)) {
            chartInstances.get(canvas).destroy();
            chartInstances.delete(canvas);
        }

        try {
            const datasets = safeParse(canvas.dataset.datasets, []).map((dataset, index) => (
                buildLineDataset(
                    dataset.data ?? dataset,
                    dataset.label ?? '',
                    dataset.colorToken ?? CHART_COLOR_TOKENS[index % CHART_COLOR_TOKENS.length],
                )
            ));

            const instance = new Chart(canvas, {
                type: canvas.dataset.type ?? 'line',
                data: {
                    labels: safeParse(canvas.dataset.labels, []),
                    datasets,
                },
                options: rutxChartOptions(),
            });

            // Registrar instancia para destrucción futura.
            chartInstances.set(canvas, instance);
        } catch (error) {
            console.warn('No se pudo inicializar la gráfica de RutX.', error);
        }
    });
}

/** Alias interno para la inicialización inicial del DOM. */
function initCharts() {
    refreshRutxCharts();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts, { once: true });
} else {
    initCharts();
}

/**
 * Evento personalizado que Alpine/Livewire despacha cuando el DOM con
 * gráficas ha sido re-renderizado por un ciclo de wire:poll.
 * En Blade: @this.dispatchTo('…') o Alpine $dispatch('rutx:refresh-charts').
 */
document.addEventListener('rutx:refresh-charts', () => refreshRutxCharts());

/**
 * Livewire 3 dispara 'livewire:navigated' al terminar de actualizar el DOM
 * en una navegación SPA. Re-inicializar por si la nueva página tiene gráficas.
 */
document.addEventListener('livewire:navigated', () => refreshRutxCharts());

export { Chart };
