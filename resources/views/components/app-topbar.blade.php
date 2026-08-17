{{--
    <x-app-topbar>
    Barra superior de RutX Web.

    Responsabilidades:
      - Mostrar marca RutX.
      - Mostrar el indicador de sincronización (<x-sync-status>).
      - Listar las 6 pestañas de módulo leídas de config/navigation.php.
      - Marcar la pestaña activa con fondo rutx-primary-dark y subrayado
        de 3 px rutx-accent.

    Props: ninguna. El estado activo se deriva de route()->getName().

    Sprint 3 · Etapa 7 — pill funcional vía <x-sync-status>.
    Sin endpoint de salud en el contrato v2, la topbar la renderiza en estado
    desconocido (SIN CONEXIÓN). Cuando exista integración del ApiClient con
    polling del monitoreo, se le pasará :connected="$estadoReal".
--}}
<header
    class="h-[var(--rutx-height-topbar)] bg-rutx-primary flex items-center justify-between px-6 shadow-sm z-20 relative"
    role="banner"
>
    {{-- Izquierda: marca + pill de sincronización neutral --}}
    <div class="flex items-center gap-5">
        {{-- Marca --}}
        <span class="text-white font-bold text-xl tracking-wider select-none" aria-label="RutX Web">
            RUTX
        </span>

        {{-- Pill de sincronización — estado neutral hasta la integración real (H-11) --}}
        <x-sync-status connected="neutral" />
    </div>

    {{-- Navegación de módulos --}}
    <nav class="flex h-full" aria-label="Módulos">
        @php
            $currentRoute = Route::currentRouteName() ?? '';
        @endphp

        @foreach(config('navigation.modules', []) as $moduleKey => $module)
            @php
                $isActive = str_starts_with($currentRoute, $moduleKey . '.');
                $defaultRoute = $module['default_route'] ?? '';
                $href = (Route::has($defaultRoute)) ? route($defaultRoute) : '#';
            @endphp

            <a
                href="{{ $href }}"
                id="topbar-module-{{ $moduleKey }}"
                class="flex items-center px-4 h-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rutx-accent focus-visible:ring-inset
                    {{ $isActive
                        ? 'text-white border-b-[3px] border-rutx-accent bg-rutx-primary-dark'
                        : 'text-white/70 hover:text-white hover:bg-white/10 border-b-[3px] border-transparent' }}"
                aria-current="{{ $isActive ? 'page' : 'false' }}"
            >
                <span class="text-sm font-medium">{{ $module['label'] }}</span>
            </a>
        @endforeach
    </nav>
</header>
