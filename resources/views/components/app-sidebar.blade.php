{{--
    <x-app-sidebar>
    Barra lateral contextual de RutX Web.

    Responsabilidades:
      - Mostrar título del módulo activo.
      - Listar exclusivamente las vistas del módulo activo desde config/navigation.php.
      - Vista activa: fondo rutx-primary-dark + borde izquierdo 4 px rutx-accent.
      - Colapso a 64 px (4rem) con botón semántico de 48 × 48 px, aria-expanded
        y gestión de foco por teclado.
      - Cada enlace muestra su icono local mediante <x-navigation-icon>.

    Props: ninguna. El estado activo se deriva de route()->getName().
    Mecanismo de colapso: controlado por JS en resources/js/app.js vía data-sidebar.
--}}
@php
    $currentRoute = Route::currentRouteName() ?? '';
    $modules      = config('navigation.modules', []);

    $activeModuleKey = null;
    foreach ($modules as $key => $module) {
        if (str_starts_with($currentRoute, $key . '.')) {
            $activeModuleKey = $key;
            break;
        }
    }
    $currentModule = $activeModuleKey ? ($modules[$activeModuleKey] ?? null) : null;
@endphp

<aside
    id="app-sidebar"
    data-sidebar
    data-collapsed="false"
    class="group/sidebar
           relative flex flex-col bg-rutx-primary-dark shadow-sm z-10 shrink-0
           w-64 data-[collapsed=true]:w-16
           transition-[width] duration-200 ease-in-out"
    aria-label="Navegación de módulo"
>
    {{-- Botón de colapso --}}
    <button
        type="button"
        data-sidebar-toggle
        class="absolute -right-4 top-8
               flex items-center justify-center
               w-12 h-12 rounded-full
               bg-rutx-primary-dark border border-white/20
               text-white/60 hover:text-white hover:bg-rutx-primary
               transition-colors z-20
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rutx-accent"
        aria-controls="app-sidebar"
        aria-expanded="true"
        aria-label="Colapsar barra lateral"
    >
        <svg
            class="w-4 h-4 transition-transform group-data-[collapsed=true]/sidebar:rotate-180"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            fill="currentColor"
            aria-hidden="true"
        >
            <path fill-rule="evenodd"
                  d="M11.78 3.22a.75.75 0 0 1 0 1.06L8.06 8l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                  clip-rule="evenodd" />
        </svg>
    </button>

    @if($currentModule)
        {{-- Cabecera del módulo --}}
        <div class="h-[var(--rutx-height-topbar)] flex items-center px-6 border-b border-white/10 overflow-hidden">
            <h2
                class="text-white font-bold tracking-wider uppercase text-sm whitespace-nowrap
                       transition-opacity duration-150
                       group-data-[collapsed=true]/sidebar:opacity-0
                       group-data-[collapsed=true]/sidebar:pointer-events-none"
            >
                {{ $currentModule['label'] }}
            </h2>
        </div>

        {{-- Vistas del módulo activo --}}
        <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden" aria-label="{{ $currentModule['label'] }}">
            <ul class="space-y-1" role="list">
                @foreach($currentModule['views'] as $viewRoute => $view)
                    @php
                        $isViewActive = ($currentRoute === $viewRoute);
                        $href         = Route::has($viewRoute) ? route($viewRoute) : '#';
                        $viewId       = 'sidebar-' . str_replace(['.', '_'], '-', $viewRoute);
                    @endphp
                    <li role="listitem">
                        <a
                            id="{{ $viewId }}"
                            href="{{ $href }}"
                            class="flex items-center px-5 py-3 gap-3
                                   border-l-4 min-h-[48px]
                                   transition-colors
                                   focus-visible:outline-none focus-visible:ring-2
                                   focus-visible:ring-inset focus-visible:ring-rutx-accent
                                   {{ $isViewActive
                                       ? 'bg-rutx-primary-dark border-l-rutx-accent text-white'
                                       : 'border-l-transparent text-white/75 hover:bg-white/10 hover:text-white' }}"
                            aria-current="{{ $isViewActive ? 'page' : 'false' }}"
                            title="{{ $view['label'] }}"
                        >
                            {{-- Icono local siempre visible --}}
                            <x-navigation-icon
                                :name="$view['icon']"
                                class="w-5 h-5 shrink-0 text-current"
                                aria-hidden="true"
                            />

                            {{-- El texto se oculta vía sr-only al colapsar --}}
                            <span
                                class="text-sm font-medium whitespace-nowrap truncate
                                       group-data-[collapsed=true]/sidebar:sr-only"
                            >{{ $view['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

    @else
        {{-- Placeholder cuando no hay módulo seleccionado --}}
        <div class="flex-1 flex items-center justify-center p-6 text-white/40 text-sm text-center">
            <span class="group-data-[collapsed=true]/sidebar:hidden">
                Seleccione un módulo
            </span>
        </div>
    @endif
</aside>
