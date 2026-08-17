{{--
    <x-kpi-card> — Tarjeta de indicador clave de rendimiento.

    Props:
      @prop string       $title   Etiqueta del KPI (texto en mayúsculas pequeñas).
      @prop float|string $value   Valor principal. Si es numérico (int|float) se
                                  formatea con Money::format y se muestra en font-mono
                                  text-right (guidelines §5.2). Si es string se muestra
                                  tal cual (p. ej. "42 / 50", "84%").
      @prop string|null  $delta   Etiqueta de variación (opcional). P. ej. "+5.2%".
      @prop string       $status  Color semántico del delta: success, warning, error, unknown.
      @prop mixed        $icon    Slot opcional con SVG de 20px (heredará text-rutx-primary).
--}}
@props([
    'title',
    'value',
    'delta' => null,
    'status' => 'unknown', // success, warning, error, unknown
    'icon' => null,
])

@php
    // Formateo interno: si value es numérico, aplicar Money::format (fuente única).
    // Los valores string (ratio, porcentaje, texto) se muestran sin transformar.
    $isMonetary = is_int($value) || is_float($value);
    $displayValue = $isMonetary ? \App\Support\Money::format((float) $value) : $value;

    $statusColor = match($status) {
        'success' => 'text-rutx-status-success',
        'warning' => 'text-rutx-status-warning',
        'error'   => 'text-rutx-status-error',
        default   => 'text-rutx-status-unknown',
    };
@endphp

<div class="bg-rutx-surface border border-rutx-border rounded-lg p-4 shadow-[var(--rutx-shadow-base)] hover:shadow-[var(--rutx-shadow-hover)] transition-shadow">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium text-rutx-text-muted uppercase tracking-wider">{{ $title }}</span>
        @if($icon)
            <div class="text-rutx-primary w-5 h-5">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="flex items-baseline space-x-2">
        {{-- font-mono + text-right cuando es monetario (guidelines §5.2) --}}
        <span class="text-[28px] font-bold text-rutx-text {{ $isMonetary ? 'font-mono text-right' : '' }}">
            {{ $displayValue }}
        </span>

        @if($delta)
            <span class="text-xs font-semibold {{ $statusColor }} bg-current/10 px-1.5 py-0.5 rounded">
                {{ $delta }}
            </span>
        @endif
    </div>
</div>
