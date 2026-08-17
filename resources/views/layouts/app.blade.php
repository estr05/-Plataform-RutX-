<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO: título dinámico por vista --}}
    @php
        $currentRoute = Route::currentRouteName() ?? '';
        $modules      = config('navigation.modules', []);
        $viewTitle    = null;
        $moduleTitle  = null;
        foreach ($modules as $moduleKey => $module) {
            if (!str_starts_with($currentRoute, $moduleKey . '.')) { continue; }
            $moduleTitle = $module['label'];
            $viewTitle   = $module['views'][$currentRoute]['label'] ?? null;
            break;
        }
        $pageTitle = collect([$viewTitle, $moduleTitle, config('app.name', 'RutX Web')])
            ->filter()
            ->implode(' · ');
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="RutX Web — Plataforma de administración y operaciones de rutas.">

    {{-- Assets compilados con Vite — sin CDN externos --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-rutx-bg text-rutx-text font-sans antialiased">

    <div class="min-h-screen flex flex-col">

        {{-- Topbar (64 px) — lee módulos de config/navigation.php --}}
        <x-app-topbar />

        <div class="flex flex-1 overflow-hidden">

            {{-- Sidebar (256 px expandido / 64 px colapsado) --}}
            {{-- Lee vistas del módulo activo desde config/navigation.php --}}
            <x-app-sidebar />

            {{-- Área de contenido principal --}}
            <main
                id="main-content"
                class="flex-1 overflow-y-auto p-6 bg-rutx-bg"
                tabindex="-1"
            >
                {{ $slot }}
            </main>

        </div>
    </div>

    {{-- Feedback stack (toasts) --}}
    <x-feedback-stack />

</body>
</html>
