@php
    $name = $getUserName($record);
    $title = $getUserTitle($record);
    $image = $getUserImage($record) ?: asset('images/placeholder.png');
@endphp

<div class="flex items-center gap-3">
    <img src="{{ $image }}" alt="{{ $name ?? __('admin.fields.user') }}" class="h-9 w-9 rounded-full object-cover ring-1 ring-gray-200">
    <div class="min-w-0">
        @if (filled($name))
            <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $name }}</p>
        @endif
        @if (filled($title))
            <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $title }}</p>
        @endif
    </div>
</div>
