@props(['status' => 'unknown', 'label'])

@php
    $classes = match($status) {
        'success' => 'text-rutx-status-success bg-rutx-status-success/12',
        'warning' => 'text-rutx-status-warning bg-rutx-status-warning/12',
        'error' => 'text-rutx-status-error bg-rutx-status-error/12',
        default => 'text-rutx-status-unknown bg-rutx-status-unknown/12'
    };
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[12px] font-semibold {{ $classes }}">
    {{ $label }}
</span>
