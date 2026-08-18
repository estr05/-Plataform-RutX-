{{--
    Home — pantalla posterior al login.

    Lista los módulos desde config/navigation.php (fuente única) y enlaza
    a la ruta por defecto de cada uno. En sprint/2 el listado se filtra por
    permiso del usuario (RBAC) vía Gate.
--}}
<x-app-layout>
    <div class="flex flex-col gap-6">

        <x-page-header
            title="Bienvenido"
            subtitle="Plataforma web de oficina y administración"
        >
            <x-slot name="actions">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 h-[var(--rutx-height-button)] px-4 rounded-[var(--rutx-radius-md)]
                               border border-rutx-border bg-rutx-surface text-rutx-text text-sm font-medium
                               hover:bg-rutx-surface-grey transition-colors
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-rutx-accent"
                    >
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M6.22 3.22a.75.75 0 0 1 1.06 0l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        {{ __('Cerrar sesión') }}
                    </button>
                </form>
            </x-slot>
        </x-page-header>

        {{-- Módulos disponibles — filtrados por permiso RBAC (sprint/2) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(config('navigation.modules', []) as $moduleKey => $module)
                @php
                    if (! Auth::user()->can($module['permission'] ?? '')) {
                        continue;
                    }

                    $href = Route::has($module['default_route']) ? route($module['default_route']) : '#';
                @endphp
                <a
                    href="{{ $href }}"
                    class="group bg-rutx-surface rounded-[var(--rutx-radius-lg)] border border-rutx-border p-5
                           flex items-start gap-4 transition-all
                           hover:border-rutx-accent hover:shadow-[var(--rutx-shadow-hover)]"
                >
                    <span class="shrink-0 flex items-center justify-center w-11 h-11 rounded-[var(--rutx-radius-md)] bg-rutx-primary text-white">
                        <x-navigation-icon :name="$module['icon']" class="w-5 h-5 text-current" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block font-semibold text-rutx-text group-hover:text-rutx-primary">{{ $module['label'] }}</span>
                        <span class="block text-sm text-rutx-text-muted mt-0.5">{{ count($module['views']) }} {{ __('vistas') }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
