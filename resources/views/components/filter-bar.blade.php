<div class="bg-rutx-surface border border-rutx-border rounded-lg p-4 mb-6 shadow-[var(--rutx-shadow-base)] flex flex-wrap items-end gap-4">
    <div class="flex-1 flex flex-wrap gap-4">
        {{ $slot }}
    </div>

    <div class="flex items-center space-x-3 shrink-0">
        @if(isset($actions))
            {{ $actions }}
        @else
            <button type="button" class="h-[var(--rutx-height-button)] px-6 rounded-xl border border-rutx-accent text-rutx-accent hover:bg-rutx-accent-light transition-colors font-medium text-sm">
                Limpiar
            </button>
            <button type="submit" class="h-[var(--rutx-height-button)] px-6 rounded-xl bg-rutx-accent text-white hover:bg-rutx-accent-hover transition-colors font-medium text-sm">
                Consultar
            </button>
        @endif
    </div>
</div>
