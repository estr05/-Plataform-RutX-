{{--
    <x-module-scaffold>
    Plantilla scaffold reutilizable para todas las vistas de módulo.

    Props:
      @prop string $note  Nota opcional para el desarrollador sobre el estado scaffold.

    Etiquetas de módulo y vista:
      - Resueltas EXCLUSIVAMENTE desde config/navigation.php mediante la ruta actual
        (Route::currentRouteName()).
      - Elimina la posibilidad de duplicación o divergencia entre vistas y configuración.

    Día 2 — scaffold sin endpoint v2.
    No contiene datos falsos, filtros simulados ni llamadas HTTP.
--}}
@props([
    'note' => 'Esta pantalla es un scaffold del Día 2. El contenido funcional se conecta en sprints posteriores mediante /api/v2/web/*.',
])

@php
    $currentRoute = Route::currentRouteName() ?? '';
    $moduleLabel  = '';
    $viewLabel    = '';

    foreach (config('navigation.modules', []) as $moduleKey => $module) {
        if (! str_starts_with($currentRoute, $moduleKey . '.')) {
            continue;
        }

        $moduleLabel = $module['label'];
        $viewLabel   = $module['views'][$currentRoute]['label'] ?? '';
        break;
    }
@endphp

<x-app-layout>
    <div class="flex flex-col gap-6">
        {{-- Breadcrumb: deriva Módulo · Vista automáticamente --}}
        <x-breadcrumb />

        {{-- Cabecera derivada desde navigation config --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-rutx-text">{{ $viewLabel }}</h1>
                <p class="text-sm text-rutx-text-muted mt-1">{{ $moduleLabel }}</p>
            </div>
        </div>

        {{-- Área de contenido: placeholder neutro, sin datos falsos --}}
        <div class="bg-rutx-surface rounded-[var(--rutx-radius-lg)] border border-rutx-border p-8 flex flex-col items-center justify-center min-h-[320px]">
            <x-loading-state message="Sin datos — pendiente integración v2" />

            @if($note)
                <p class="mt-4 text-xs text-rutx-text-muted text-center max-w-sm">
                    {{ $note }}
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
