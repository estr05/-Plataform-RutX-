{{--
    <x-date-range> — Selector de rango con presets Diario/Semanal/Mensual.

    Props:
      @prop string $range  Rango activo (diario|semanal|mensual). Default: semanal.

    Los botones actualizan el estado de un componente Livewire
    (wire:click="$set('range', ...)"); fuera de Livewire quedan como toggles
    inertes. aria-pressed refleja el estado para accesibilidad y el foco
    visible usa el anillo rutx-accent.
--}}
@props(['range' => 'semanal'])

<div class="flex items-center gap-1 bg-rutx-surface-grey border border-rutx-border rounded-lg p-1" role="group" aria-label="Rango de fechas">
    @foreach (['diario' => 'Diario', 'semanal' => 'Semanal', 'mensual' => 'Mensual'] as $value => $label)
        <button
            type="button"
            wire:click="$set('range', '{{ $value }}')"
            aria-pressed="{{ $range === $value ? 'true' : 'false' }}"
            class="px-3 py-1.5 text-sm rounded-md transition-colors
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rutx-accent
                   {{ $range === $value
                       ? 'bg-rutx-primary text-white'
                       : 'text-rutx-text-muted hover:bg-rutx-surface' }}"
        >{{ $label }}</button>
    @endforeach
</div>
