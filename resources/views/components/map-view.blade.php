{{--
    <x-map-view> — Mapa Leaflet + OpenStreetMap (npm/Vite, prohibido CDN).

    Props:
      @prop array  $markers Marcadores: [['lat' => float, 'lon' => float,
                             'status' => 'active'|'delayed'|'stopped'|'unknown',
                             'label' => string], ...].
      @prop string  $id      Id del contenedor (default: aleatorio).
      @prop string  $height  Altura CSS del mapa (default: 520px).
      @prop array   $center  Centro inicial [lat, lon] (default: Guadalajara).
      @prop int     $zoom    Zoom inicial (default: 12).

    Este componente es solo HTML + data-attributes; la inicialización vive en
    resources/js/modules/map.js (importado en app.js). Los colores de los
    marcadores salen de los tokens --rutx-status-*; el texto del popup se
    inserta con textContent en el módulo (sin HTML).
--}}
@props([
    'markers' => [],
    'id' => 'rutx-map-'.\Illuminate\Support\Str::random(6),
    'height' => '520px',
    'center' => [20.6, -103.4],
    'zoom' => 12,
])

<div
    class="bg-rutx-surface border border-rutx-border rounded-lg overflow-hidden shadow-[var(--rutx-shadow-base)]"
    x-data
    x-init="$nextTick(() => $dispatch('rutx:refresh-maps'))"
>
    <div
        id="{{ $id }}"
        data-rutx-map
        data-markers='{{ json_encode($markers, JSON_UNESCAPED_UNICODE) }}'
        data-center='{{ json_encode($center, JSON_UNESCAPED_UNICODE) }}'
        data-zoom="{{ $zoom }}"
        style="height: {{ $height }}; width: 100%;"
        role="region"
        aria-label="Mapa de rutas"
    ></div>
</div>
