<x-filament-panels::page>
    @php
        $locales = array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));
        $localeNames = ['ar' => 'العربية', 'en' => 'English'];
        $socialFields = [
            'social_facebook' => 'Facebook',
            'social_instagram' => 'Instagram',
            'social_linkedin' => 'LinkedIn',
            'social_youtube' => 'YouTube',
        ];
        $providerModels = \App\Filament\Pages\ManageSiteSettings::providerModels();
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
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Maintain phone, email, and map details used on contact sections.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address') }}</label>
                        <input type="text" wire:model.lazy="data.contact_address" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address URL') }}</label>
                        <input type="url" wire:model.lazy="data.address_url" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contact Phone') }}</label>
                        <input type="tel" wire:model.lazy="data.contact_phone" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contact Email') }}</label>
                        <input type="email" wire:model.lazy="data.contact_email" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.strategic_consultation_url') }}</label>
                        <input
                            type="url"
                            wire:model.lazy="data.strategic_consultation_url"
                            placeholder="{{ \App\Filament\Pages\ManageSiteSettings::defaultStrategicConsultationUrl() }}"
                            dir="ltr"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100"
                        />
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
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-primary-800 dark:text-gray-100">{{ __('admin.settings.seo') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Set default multilingual SEO title and description for uncategorized pages.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 space-y-6" x-data="{ activeTab: '{{ app()->getLocale() }}' }">
                <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
                    @foreach ($locales as $locale)
                        <button type="button" @click="activeTab = '{{ $locale }}'" class="px-4 py-2.5 text-sm font-medium border-b-2"
                            :class="activeTab === '{{ $locale }}' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500'">
                            {{ $localeNames[$locale] ?? strtoupper($locale) }}
                        </button>
                    @endforeach
                </div>
                @foreach ($locales as $locale)
                    <div x-show="activeTab === '{{ $locale }}'">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Default SEO Title') }}</label>
                                <input type="text" wire:model.lazy="data.default_seo_title_{{ $locale }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Default SEO Description') }}</label>
                                <input type="text" wire:model.lazy="data.default_seo_description_{{ $locale }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="flex justify-end">
                    <x-filament::button type="button" wire:click="saveSeo" wire:loading.attr="disabled" wire:target="saveSeo">
                        <span wire:loading.remove wire:target="saveSeo">{{ __('admin.settings.save_seo') }}</span>
                        <span wire:loading wire:target="saveSeo">{{ __('admin.settings.saving') }}</span>
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
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Configure provider, model, and feature flags for AI-powered SEO generation.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 space-y-6"
                x-data="{
                    provider: @entangle('data.openai_provider'),
                    model: @entangle('data.openai_model'),
                    providerModels: @js($providerModels),
                    syncModel() {
                        if (this.provider === 'custom') return;
                        const options = Object.keys(this.providerModels[this.provider] ?? {});
                        if (! options.length) { this.model = ''; return; }
                        if (! options.includes(this.model)) this.model = options[0];
                    },
                }"
                x-init="syncModel()"
                x-effect="syncModel()"
            >
                <div class="grid lg:grid-cols-3 grid-cols-1 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.openai_api_key') }}</label>
                        <div class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800">
                            {{ filled(config('services.openai.api_key')) ? __('Configured in server environment') : __('Not configured in server environment') }}
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.openai_provider') }}</label>
                        <select wire:model.live="data.openai_provider" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                            <option value="openai">OpenAI</option>
                            <option value="openrouter">OpenRouter</option>
                            <option value="custom">{{ __('admin.settings.provider_custom') }}</option>
                        </select>
                    </div>
                    <div class="space-y-1.5" x-show="provider === 'custom'" x-cloak>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.openai_custom_url') }}</label>
                        <input type="url" wire:model.lazy="data.openai_custom_url" placeholder="https://api.example.com/v1" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
                    </div>
                    <div class="space-y-1.5" x-show="provider !== 'custom'">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.openai_model') }}</label>
                        <select wire:model.live="data.openai_model" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                            <template x-for="[value, label] in Object.entries(providerModels[provider] ?? {})" :key="value">
                                <option :value="value" x-text="label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="space-y-1.5" x-show="provider === 'custom'" x-cloak>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.openai_model') }}</label>
                        <input type="text" wire:model.lazy="data.openai_model" placeholder="gpt-4o-mini" dir="ltr" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm" />
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
