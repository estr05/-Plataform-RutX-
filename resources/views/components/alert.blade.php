@props(['type' => 'success', 'message'])

@php
    $classes = match($type) {
        'success' => 'bg-rutx-status-success/10 border-rutx-status-success/20 text-rutx-status-success',
        'warning' => 'bg-rutx-status-warning/10 border-rutx-status-warning/20 text-rutx-status-warning',
        'error' => 'bg-rutx-status-error/10 border-rutx-status-error/20 text-rutx-status-error',
        default => 'bg-rutx-secondary/10 border-rutx-secondary/20 text-rutx-primary'
    };
@endphp

<div class="p-4 rounded-lg border {{ $classes }} flex items-start" role="alert" tabindex="0">
    <div class="flex-1 text-sm font-medium">
        {{ $message }}
    </div>
</div>
