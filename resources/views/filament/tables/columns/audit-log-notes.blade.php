@php
    /** @var \Spatie\Activitylog\Models\Activity|null $record */
    $summary = $record ? \App\Filament\Resources\AuditLogResource::buildDynamicNotes($record) : null;
@endphp

@if (filled($summary))
    <details class="text-sm leading-6">
        <summary class="cursor-pointer text-primary-600 dark:text-primary-400">
            {{ __('Show details') }}
        </summary>
        <div class="mt-2 whitespace-normal break-words text-gray-700 dark:text-gray-200">
            {{ $summary }}
        </div>
    </details>
@else
    <span class="text-gray-400">{{ __('-') }}</span>
@endif
