@php
    $status = $record->status;
    $statusColor = $status?->color() ?? 'gray';
    $colorMap = [
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'primary' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    ];
    $badgeClasses = $colorMap[$statusColor] ?? $colorMap['gray'];
@endphp

@if (auth()->user()?->can('update-knowledge-resources-status'))
    <button
        type="button"
        wire:click="mountTableAction('changeStatus', '{{ $record->getKey() }}')"
        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium {{ $badgeClasses }} cursor-pointer hover:opacity-80 transition-opacity"
    >
        {{ $status?->label() ?? '-' }}
        <x-filament::icon icon="phosphor-pencil-simple-duotone" class="h-3 w-3 opacity-70" />
    </button>
@else
    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium opacity-50 {{ $badgeClasses }}">
        {{ $status?->label() ?? '-' }}
    </span>
@endif
