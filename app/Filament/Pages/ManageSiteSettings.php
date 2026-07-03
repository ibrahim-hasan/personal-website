<?php

namespace App\Filament\Pages;

use App\Filament\Components\TranslatableTabs;
use App\Models\Setting;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.configuration');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.setting.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.setting.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('Manage Site Settings');
    }

    public function getTitle(): string
    {
        return __('Manage Site Settings');
    }

    protected string $view = 'filament.pages.manage-site-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = Setting::query()->pluck(Setting::valueColumn(), 'key')->toArray();
        $settings = $this->expandTranslatableSettings($settings, [
            'default_seo_title',
            'default_seo_description',
            'about_doctor_description',
        ]);

        $this->data = array_merge([
            'openai_provider' => 'openai',
            'openai_model' => array_key_first(self::providerModels()['openai']) ?: '',
            'openai_custom_url' => '',
            'openai_api_key' => '',
            'ai_seo_enabled' => false,
            'ai_seo_expert_enabled' => false,
            'strategic_consultation_url' => self::defaultStrategicConsultationUrl(),
        ], $settings);

        foreach ($this->supportedLocales() as $locale) {
            $this->data["default_seo_title_{$locale}"] = $this->data["default_seo_title_{$locale}"] ?: ($locale === 'ar'
                ? 'إبراهيم حسن | هندسة منتجات الذكاء الاصطناعي'
                : 'Ibrahim Hasan | AI Product Engineering');

            $this->data["default_seo_description_{$locale}"] = $this->data["default_seo_description_{$locale}"] ?: ($locale === 'ar'
                ? 'بناء وإصلاح منصات Laravel وDjango ومساعدات الذكاء الاصطناعي ومسارات التشغيل.'
                : 'Building and repairing Laravel, Django, AI assistant, automation, and production systems.');

            $this->data["about_doctor_description_{$locale}"] = $this->data["about_doctor_description_{$locale}"] ?: (self::defaultAboutDoctorDescription()[$locale] ?? '');
        }

        $this->data['strategic_consultation_url'] = $this->data['strategic_consultation_url'] ?: self::defaultStrategicConsultationUrl();

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('translatable.locales', ['ar', 'en']);
        $translationsTabsSchema = [];

        foreach ($locales as $locale) {
            $translationsTabsSchema[$locale] = [
                RichEditor::make("about_doctor_description_{$locale}")
                    ->label(__('admin.settings.about_doctor_description'))
                    ->columnSpanFull(),
            ];
        }

        return $schema
            ->statePath('data')
            ->components([
                TranslatableTabs::make($translationsTabsSchema, columns: 1),
            ]);
    }

    public static function defaultAboutDoctorDescription(): array
    {
        return [
            'ar' => '<p>يبني إبراهيم حسن منتجات عملية باستخدام Laravel وDjango والذكاء الاصطناعي، مع تركيز على لوحات الإدارة، مساعدات الدعم، الأتمتة، ومسارات النشر التي يمكن التحقق منها في المتصفح.</p>',
            'en' => '<p>Ibrahim Hasan builds practical Laravel, Django, and AI-enabled products with a focus on admin systems, support assistants, automation, deployment paths, and browser-verified production behavior.</p>',
        ];
    }

    public static function defaultStrategicConsultationUrl(): string
    {
        $email = setting_value('contact_email', 'contact', 'hello@ibrahimhasan.dev');

        return 'mailto:'.$email;
    }

    public function saveWebsiteContent(): void
    {
        $state = $this->form->getState();
        $websiteContentData = $this->mergeTranslatableSettings($state, ['about_doctor_description']);

        Setting::setValue(
            'about_doctor_description',
            $websiteContentData['about_doctor_description'] ?? [],
            'website_content',
        );

        Setting::query()
            ->where('key', 'about_doctor_description')
            ->where('group', 'website_content')
            ->update(['label' => __('admin.settings.about_doctor_description')]);

        $this->data = array_merge($this->data, $state);

        $this->notifySaved();
    }

    public function saveSocial(): void
    {
        $this->saveGroup([
            'social_facebook',
            'social_twitter',
            'social_instagram',
            'social_linkedin',
            'social_youtube',
        ], 'social');
    }

    public function saveContact(): void
    {
        $this->saveGroup([
            'contact_address',
            'address_url',
            'contact_phone',
            'contact_email',
            'whatsapp_number',
            'strategic_consultation_url',
        ], 'contact');

        Setting::query()
            ->where('key', 'strategic_consultation_url')
            ->where('group', 'contact')
            ->update(['label' => __('admin.settings.strategic_consultation_url')]);
    }

    public function saveSeo(): void
    {
        $seoData = $this->mergeTranslatableSettings($this->data, [
            'default_seo_title',
            'default_seo_description',
        ]);
        Setting::setValue('default_seo_title', $seoData['default_seo_title'] ?? [], 'seo');
        Setting::setValue('default_seo_description', $seoData['default_seo_description'] ?? [], 'seo');

        $this->notifySaved();
    }

    public function saveAi(): void
    {
        $provider = (string) ($this->data['openai_provider'] ?? 'openai');
        $customUrl = (string) ($this->data['openai_custom_url'] ?? '');
        $baseUrl = $this->providerToBaseUrl($provider, $customUrl);

        Setting::setValue('openai_api_key', (string) ($this->data['openai_api_key'] ?? ''), 'ai');
        Setting::setValue('openai_provider', $provider, 'ai');
        Setting::setValue('openai_base_url', $baseUrl, 'ai');
        Setting::setValue('openai_custom_url', $customUrl, 'ai');
        Setting::setValue('openai_model', (string) ($this->data['openai_model'] ?? ''), 'ai');
        Setting::setValue('ai_seo_enabled', ! empty($this->data['ai_seo_enabled']), 'ai');
        Setting::setValue('ai_seo_expert_enabled', ! empty($this->data['ai_seo_expert_enabled']), 'ai');

        $this->notifySaved();
    }

    public static function providerModels(): array
    {
        return [
            'openai' => [
                'gpt-4o-mini' => 'GPT-4o mini',
                'gpt-4.1-mini' => 'GPT-4.1 mini',
                'gpt-4.1' => 'GPT-4.1',
            ],
            'openrouter' => [
                'openai/gpt-4o-mini' => 'OpenAI GPT-4o mini',
                'openai/gpt-4.1-mini' => 'OpenAI GPT-4.1 mini',
                'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet',
            ],
        ];
    }

    public static function providerBaseUrlMap(): array
    {
        return [
            'openai' => 'https://api.openai.com/v1',
            'openrouter' => 'https://openrouter.ai/api/v1',
        ];
    }

    private function providerToBaseUrl(string $provider, string $customUrl = ''): string
    {
        if ($provider === 'custom') {
            return $customUrl;
        }

        return self::providerBaseUrlMap()[$provider] ?? 'https://api.openai.com/v1';
    }

    /**
     * @param  list<string>  $keys
     */
    private function saveGroup(array $keys, string $group): void
    {
        foreach ($keys as $key) {
            Setting::setValue($key, $this->data[$key] ?? null, $group);
        }

        $this->notifySaved();
    }

    private function notifySaved(): void
    {
        Notification::make()
            ->title(__('Settings saved successfully'))
            ->success()
            ->send();
    }

    private function supportedLocales(): array
    {
        return array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));
    }

    private function expandTranslatableSettings(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $value = $data[$field] ?? null;
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            }

            foreach ($this->supportedLocales() as $locale) {
                $data["{$field}_{$locale}"] = is_array($value) ? ($value[$locale] ?? '') : '';
            }
        }

        return $data;
    }

    /**
     * @param  list<string>  $fields
     */
    private function mergeTranslatableSettings(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $localized = [];

            foreach ($this->supportedLocales() as $locale) {
                $key = "{$field}_{$locale}";
                $localized[$locale] = (string) ($data[$key] ?? '');
                unset($data[$key]);
            }

            $data[$field] = $localized;
        }

        return $data;
    }
}
