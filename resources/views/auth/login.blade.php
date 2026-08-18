{{--
    Página de inicio de sesión — autenticación local (sprint/2).

    Página autónoma (sin topbar/sidebar): card centrada sobre fondo
    rutx-primary-dark usando los tokens del design system.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Iniciar sesión') }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-rutx-primary-dark min-h-screen flex items-center justify-center p-4">

    <main class="w-full max-w-md">
        <div class="bg-rutx-surface rounded-[var(--rutx-radius-lg)] shadow-[var(--rutx-shadow-hover)] overflow-hidden">

            {{-- Cabecera de marca --}}
            <div class="bg-rutx-primary px-6 py-6 text-center">
                <p class="text-white font-bold text-2xl tracking-tight">{{ config('app.name') }}</p>
                <p class="text-white/60 text-sm mt-1">{{ __('Plataforma web de oficina y administración') }}</p>
            </div>

            {{-- Formulario --}}
            <form method="POST" action="{{ route('login') }}" class="p-6 space-y-4" novalidate>
                @csrf

                @if($errors->any())
                    <div class="rounded-[var(--rutx-radius-md)] bg-[var(--rutx-alert-error-bg)] border border-[var(--rutx-status-error)]/20 px-4 py-3 text-sm text-[var(--rutx-status-error)]" role="alert">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- Correo --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-rutx-text mb-1.5">{{ __('Correo electrónico') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full h-[var(--rutx-height-input)] rounded-[var(--rutx-radius-md)] border border-rutx-border bg-white px-3 text-sm text-rutx-text
                               focus:outline-none focus:ring-2 focus:ring-rutx-accent focus:border-rutx-accent"
                        placeholder="usuario@rutx.mx"
                    >
                </div>

                {{-- Contraseña --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-rutx-text mb-1.5">{{ __('Contraseña') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full h-[var(--rutx-height-input)] rounded-[var(--rutx-radius-md)] border border-rutx-border bg-white px-3 text-sm text-rutx-text
                               focus:outline-none focus:ring-2 focus:ring-rutx-accent focus:border-rutx-accent"
                    >
                </div>

                {{-- Recordarme --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-rutx-text-muted">
                        <input type="checkbox" name="remember" value="1" class="rounded border-rutx-border text-rutx-accent focus:ring-rutx-accent">
                        {{ __('Recordarme') }}
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full h-[var(--rutx-height-button)] rounded-[var(--rutx-radius-md)] bg-rutx-accent text-white font-semibold text-sm
                           hover:bg-[var(--rutx-accent-hover)] transition-colors
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-rutx-accent focus-visible:ring-offset-2"
                >
                    {{ __('Iniciar sesión') }}
                </button>
            </form>
        </div>

        <p class="text-center text-white/50 text-xs mt-4">
            © {{ date('Y') }} {{ config('app.name') }} — acceso restringido
        </p>
    </main>

</body>
</html>
