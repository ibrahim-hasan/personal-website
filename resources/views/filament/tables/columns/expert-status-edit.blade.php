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

@if ($record->is_approved && auth()->user()?->can('update-experts-status'))
    <button
        type="button"
        wire:click="mountTableAction('changeStatus', '{{ $record->getKey() }}')"
        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium {{ $badgeClasses }} cursor-pointer hover:opacity-80 transition-opacity"
    >
        {{ $status?->label() ?? '-' }}
        <svg class="w-3 h-3 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M225.91,74.31,181.7,30.09a6,6,0,0,0-8.48,0L38.14,165.17A6,6,0,0,0,36.4,171l-12.35,53.3a6,6,0,0,0,7.72,7.15L85.07,219.4a6,6,0,0,0,2.76-1.59L225.91,79.83A6,6,0,0,0,225.91,74.31Z" opacity="0.2"></path><path d="M227.32,73.72,182.28,28.69a15.86,15.86,0,0,0-11.31-4.69h-.06a15.9,15.9,0,0,0-11.29,4.72L36.41,152.43A15.94,15.94,0,0,0,31.89,163L19.55,216.28a4,4,0,0,0,5.15,4.52L78,208.47a15.89,15.89,0,0,0,10.56-4.51L227.32,65.23A16,16,0,0,0,227.32,73.72ZM78.76,196.08l-42.49,9.59,9.49-42.49,96.86-96.47,33,33ZM185.76,89.17l-33-33,18.18-18.11a4,4,0,0,1,2.83-1.18h0a4,4,0,0,1,2.82,1.17l45,45a4,4,0,0,1,0,5.65Z"></path></svg>
    </button>
@else
    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium opacity-50 {{ $badgeClasses }}">
        {{ $status?->label() ?? '-' }}
    </span>
@endif
