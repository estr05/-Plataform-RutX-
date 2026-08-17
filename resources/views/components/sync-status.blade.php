{{--
    <x-sync-status> — Pill de estado de sincronización de la topbar (Plan Visual §2.3.2).

    Props:
      @prop bool|string|null $connected   true → CONECTADO (rutx-status-success).
                                          false → SIN CONEXIÓN (rutx-status-error).
                                          'neutral' → PENDIENTE (rutx-status-unknown);
                                          usar mientras no exista endpoint de salud.
                                          null → equivalente a false (sin conexión).
      @prop string|null      $lastSyncAt  Fecha/hora de la última sincronización.

    Estado neutral (H-11): evita mostrar "SIN CONEXIÓN" en demo/staging cuando
    la integración real aún no está disponible. La topbar pasa connected="neutral"
    hasta que exista el endpoint de salud del Sincronizador.
--}}
@props([
    'connected' => null,
    'lastSyncAt' => null,
])

@php
    $isConnected = $connected === true;
    $isNeutral   = $connected === 'neutral';

    if ($isConnected) {
        $dotClass  = 'bg-rutx-status-success';
        $textClass = 'text-rutx-status-success';
        $label     = 'CONECTADO';
        $ariaLabel = 'conectado';
    } elseif ($isNeutral) {
        $dotClass  = 'bg-rutx-status-unknown';
        $textClass = 'text-white/50';
        $label     = 'PENDIENTE';
        $ariaLabel = 'estado pendiente de integración';
    } else {
        $dotClass  = 'bg-rutx-status-error';
        $textClass = 'text-rutx-status-error';
        $label     = 'SIN CONEXIÓN';
        $ariaLabel = 'sin conexión';
    }

    $titleAttr = $isNeutral
        ? 'Estado de conexión disponible en la integración real'
        : 'Última sincronización: ' . ($lastSyncAt ?? 'no disponible');
@endphp

<div
    class="flex items-center gap-2 bg-white/10 rounded-full px-3 py-1 text-xs font-medium text-white/70"
    role="status"
    aria-live="polite"
    aria-label="Estado de sincronización: {{ $ariaLabel }}"
    title="{{ $titleAttr }}"
>
    <span
        class="w-2 h-2 rounded-full {{ $dotClass }}"
        aria-hidden="true"
    ></span>
    <span>Sincronización</span>
    <span class="{{ $textClass }} font-semibold uppercase tracking-widest">
        {{ $label }}
    </span>
</div>
