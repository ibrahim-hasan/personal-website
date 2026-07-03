@php
    $email = $record->user?->email;
    $phone = $record->user?->phone
        ? ($record->user->phone_country_code ?? '') . $record->user->phone
        : null;
@endphp

@if ($email || $phone)
    <div class="space-y-1">
        @if ($email)
            <div class="flex items-center gap-1.5">
                <x-filament::icon icon="phosphor-envelope-simple-duotone" class="h-4 w-4 text-gray-500 shrink-0" />
                <a href="mailto:{{ $email }}" class="text-primary-600 hover:underline">{{ $email }}</a>
            </div>
        @endif

        @if ($phone)
            <div class="flex items-center gap-1.5">
                <x-filament::icon icon="phosphor-phone-duotone" class="h-4 w-4 text-gray-500 shrink-0" />
                <a href="tel:{{ $phone }}" class="text-primary-600 hover:underline ltr">{{ $phone }}</a>
            </div>
        @endif
    </div>
@endif
