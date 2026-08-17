{{--
    <x-breadcrumb>
    Refleja "Módulo · Vista" derivando las etiquetas desde config/navigation.php.

    Props:
      @prop array $extra  Segmentos adicionales opcionales (array de strings).
                          No pasar textos hardcoded de módulo/vista; el componente
                          los deriva automáticamente de la ruta activa.

    El componente NO acepta textos hardcoded de módulo o vista por página.
    Lee los mismos identificadores de navegación que topbar y sidebar.

    Día 2 — sin datos de negocio.
--}}
@props(['extra' => []])

@php
    $currentRoute = Route::currentRouteName() ?? '';
    $modules      = config('navigation.modules', []);

    $moduleLabel = null;
    $viewLabel   = null;

    foreach ($modules as $moduleKey => $module) {
        if (!str_starts_with($currentRoute, $moduleKey . '.')) {
            continue;
        }
        $moduleLabel = $module['label'];
        if (isset($module['views'][$currentRoute])) {
            $viewLabel = $module['views'][$currentRoute]['label'];
        }
        break;
    }

    // Construir los segmentos: solo los que tienen valor
    $segments = array_filter([$moduleLabel, $viewLabel, ...$extra]);
@endphp

@if(count($segments))
    <nav class="flex text-[12px] text-rutx-text-muted mb-4" aria-label="Ruta de navegación">
        <ol class="inline-flex items-center gap-2" role="list">
            @foreach(array_values($segments) as $index => $segment)
                <li class="inline-flex items-center" role="listitem">
                    @if($index < count($segments) - 1)
                        <span class="font-medium">{{ $segment }}</span>
                        <span class="mx-2 text-rutx-border" aria-hidden="true">·</span>
                    @else
                        <span class="font-semibold text-rutx-text" aria-current="page">{{ $segment }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
