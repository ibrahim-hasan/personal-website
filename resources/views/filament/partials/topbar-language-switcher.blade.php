@php
    $current = app()->getLocale();
    $targetLocale = $current === 'ar' ? 'en' : 'ar';
@endphp

<div class="admin-topbar-tools" data-admin-tools>
    <x-filament::dropdown placement="bottom-end" teleport>
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-o-globe-alt"
                color="gray"
                size="lg"
                :label="__('admin.navigation.utilities')"
            />
        </x-slot>

        <x-filament::dropdown.list>
            <x-filament::dropdown.list.item
                tag="form"
                method="POST"
                :action="locale_switch_url($targetLocale)"
                icon="heroicon-o-language"
            >
                {{ __('admin.locales.'.$targetLocale) }}
            </x-filament::dropdown.list.item>

            <x-filament::dropdown.list.item
                tag="a"
                :href="localized_route('home')"
                target="_blank"
                rel="noopener noreferrer"
                icon="heroicon-o-arrow-top-right-on-square"
            >
                {{ __('admin.auth.open_website') }}
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
