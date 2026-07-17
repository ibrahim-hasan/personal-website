<x-filament-panels::page>
    @php
        $socialFields = [
            'social_facebook' => 'Facebook',
            'social_twitter' => 'X',
            'social_instagram' => 'Instagram',
            'social_linkedin' => 'LinkedIn',
            'social_youtube' => 'YouTube',
        ];
        $openAiModels = \App\Filament\Pages\ManageSiteSettings::openAiModels();
    @endphp

    <div class="space-y-8">
        <div class="rounded-2xl border border-primary-100/70 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-primary-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-primary-100/80 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-primary-100/80 dark:bg-primary-900/30 p-2.5">
                        <x-filament::icon icon="heroicon-o-link" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-primary-800 dark:text-gray-100">{{ __('admin.settings.social_media') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Control your social links shown across header and footer areas.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($socialFields as $field => $label)
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __($label) }}</label>
                            <input
                                type="url"
                                wire:model.lazy="data.{{ $field }}"
                                placeholder="{{ __('Enter field', ['name' => __('Url')]) }}"
                                dir="ltr"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100"
                            />
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-end">
                    <x-filament::button type="button" wire:click="saveSocial" wire:loading.attr="disabled" wire:target="saveSocial">
                        <span wire:loading.remove wire:target="saveSocial">{{ __('admin.settings.save_social_media') }}</span>
                        <span wire:loading wire:target="saveSocial">{{ __('admin.settings.saving') }}</span>
                    </x-filament::button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-primary-100/70 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-primary-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-primary-100/80 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-primary-100/80 dark:bg-primary-900/30 p-2.5">
                        <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-primary-800 dark:text-gray-100">{{ __('admin.settings.contact_information') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Maintain the direct contact channels shown on the website.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contact Phone') }}</label>
                        <input type="tel" wire:model.lazy="data.contact_phone" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contact Email') }}</label>
                        <input type="email" wire:model.lazy="data.contact_email" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('WhatsApp Number') }}</label>
                        <input type="tel" wire:model.lazy="data.whatsapp_number" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <x-filament::button type="button" wire:click="saveContact" wire:loading.attr="disabled" wire:target="saveContact">
                        <span wire:loading.remove wire:target="saveContact">{{ __('admin.settings.save_contact_information') }}</span>
                        <span wire:loading wire:target="saveContact">{{ __('admin.settings.saving') }}</span>
                    </x-filament::button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-primary-100/70 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-primary-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-primary-100/80 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-primary-100/80 dark:bg-primary-900/30 p-2.5">
                        <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-primary-800 dark:text-gray-100">{{ __('admin.settings.website_content') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Manage translatable website content sections.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="button" wire:click="saveWebsiteContent" wire:loading.attr="disabled" wire:target="saveWebsiteContent">
                        <span wire:loading.remove wire:target="saveWebsiteContent">{{ __('admin.settings.save_website_content') }}</span>
                        <span wire:loading wire:target="saveWebsiteContent">{{ __('admin.settings.saving') }}</span>
                    </x-filament::button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-primary-100/70 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-primary-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-primary-100/80 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-primary-100/80 dark:bg-primary-900/30 p-2.5">
                        <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-primary-800 dark:text-gray-100">{{ __('admin.settings.ai') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Configure the server-managed OpenAI integration and AI-powered SEO generation.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.openai_connection_status') }}</label>
                        <div class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800">
                            {{ \App\Filament\Pages\ManageSiteSettings::isOpenAiConfigured()
                                ? __('admin.settings.openai_configured_on_server')
                                : __('admin.settings.openai_not_configured_on_server') }}
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.openai_model') }}</label>
                        <select wire:model.lazy="data.openai_model" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                            @foreach ($openAiModels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <input type="checkbox" wire:model.lazy="data.ai_seo_enabled" class="w-4 h-4 rounded border-gray-300 text-primary-600">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.ai_seo_enabled') }}</span>
                    </label>
                </div>
                <div class="flex justify-end">
                    <x-filament::button type="button" wire:click="saveAi" wire:loading.attr="disabled" wire:target="saveAi">
                        <span wire:loading.remove wire:target="saveAi">{{ __('admin.settings.save_ai') }}</span>
                        <span wire:loading wire:target="saveAi">{{ __('admin.settings.saving') }}</span>
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
