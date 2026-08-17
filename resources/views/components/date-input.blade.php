{{--
    <x-date-input> — Input de fecha reutilizable.

    Props:
      @prop string $id     id del input (también es el for del label).
      @prop string $label  Texto de la etiqueta visible.

    Atributos adicionales (wire:model, name, min, max, …) se pasan vía
    $attributes al input (Livewire attribute bag). Esto permite:
        <x-date-input id="date-from" label="Desde" wire:model="dateFrom" />

    Clases centralizadas (guidelines §5.2, §3.2):
      - h-[var(--rutx-height-input)]  — altura estándar de inputs.
      - border border-rutx-border     — borde semántico.
      - rounded-lg bg-rutx-surface-grey px-3 text-sm — tipografía y fondo.
      - focus:ring-2 focus:ring-rutx-primary focus:border-rutx-primary — foco AA.
      - outline-none transition-shadow — transición suave.
--}}
@props(['id', 'label'])

<div>
    <label for="{{ $id }}" class="block text-xs font-semibold text-rutx-text-muted mb-1">
        {{ $label }}
    </label>
    <input
        id="{{ $id }}"
        type="date"
        {{ $attributes->class([
            'h-[var(--rutx-height-input)]',
            'border border-rutx-border',
            'rounded-lg bg-rutx-surface-grey px-3 text-sm',
            'focus:ring-2 focus:ring-rutx-primary focus:border-rutx-primary',
            'outline-none transition-shadow',
        ]) }}
    />
</div>
