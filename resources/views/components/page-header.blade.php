{{--
    <x-page-header> — Encabezado estándar de pantalla.

    Props:
      @prop string $title     Título H1 de la pantalla.
      @prop string $subtitle  Subtítulo opcional bajo el título (default: null).
      @slot       $actions    Acciones alineadas a la derecha (opcional).

    No acepta breadcrumb: <x-breadcrumb> se renderiza aparte y deriva
    Módulo · Vista desde config/navigation.php.
--}}
@props(['title', 'subtitle' => null])

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-[30px] leading-[1.2] font-bold text-rutx-primary tracking-tight">{{ $title }}</h1>

        @if($subtitle)
            <p class="text-sm text-rutx-text-muted mt-1">{{ $subtitle }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex items-center space-x-3">
            {{ $actions }}
        </div>
    @endif
</div>
