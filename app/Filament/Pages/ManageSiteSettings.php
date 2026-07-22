<?php

namespace App\Filament\Pages;

use App\Filament\Components\TranslatableTabs;
use App\Models\Setting;
use App\Support\Ai\OpenAiModelPolicy;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    /** @var list<string> */
    private const STATE_KEYS = [
        'about_biography',
        'ai_seo_enabled',
        'contact_email',
        'contact_phone',
        'openai_model',
        'social_facebook',
        'social_instagram',
        'social_linkedin',
        'social_twitter',
        'social_youtube',
        'whatsapp_number',
    ];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.administration');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update settings') === true;
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
        $settings = Setting::query()
            ->whereIn('key', self::STATE_KEYS)
            ->pluck(Setting::valueColumn(), 'key')
            ->toArray();
        $settings = $this->expandTranslatableSettings($settings, ['about_biography']);

        $this->data = array_merge([
            'openai_model' => self::defaultOpenAiModel(),
            'ai_seo_enabled' => false,
        ], $settings);

        if (! array_key_exists((string) $this->data['openai_model'], self::openAiModels())) {
            $this->data['openai_model'] = self::defaultOpenAiModel();
        }

        foreach ($this->supportedLocales() as $locale) {
            $this->data["about_biography_{$locale}"] = trim(strip_tags(
                (string) ($this->data["about_biography_{$locale}"] ?: (self::defaultAboutBiography()[$locale] ?? '')),
            ));
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('translatable.locales', ['ar', 'en']);
        $translationsTabsSchema = [];

        foreach ($locales as $locale) {
            $translationsTabsSchema[$locale] = [
                Textarea::make("about_biography_{$locale}")
                    ->label(__('admin.settings.about_biography'))
                    ->rows(7)
                    ->maxLength(3000)
                    ->columnSpanFull(),
            ];
        }

        return $schema
            ->statePath('data')
            ->components([
                TranslatableTabs::make($translationsTabsSchema, columns: 1),
            ]);
    }

    public static function defaultAboutBiography(): array
    {
        return [
            'ar' => (string) trans('site.about.body', [], 'ar'),
            'en' => (string) trans('site.about.body', [], 'en'),
        ];
    }

    public function saveWebsiteContent(): void
    {
        $this->authorizeSettingsUpdate();
        $this->validate($this->websiteContentRules());

        $state = $this->form->getState();
        $websiteContentData = $this->mergeTranslatableSettings($state, ['about_biography']);
        $biography = [];

        foreach ($this->supportedLocales() as $locale) {
            $biography[$locale] = trim(strip_tags(
                (string) ($websiteContentData['about_biography'][$locale] ?? ''),
            ));
            $this->data["about_biography_{$locale}"] = $biography[$locale];
        }

        Setting::setValue(
            'about_biography',
            $biography,
            'website_content',
        );

        Setting::query()
            ->where('key', 'about_biography')
            ->where('group', 'website_content')
            ->update(['label' => __('admin.settings.about_biography')]);

        $this->notifySaved();
    }

    public function saveSocial(): void
    {
        $this->authorizeSettingsUpdate();
        $this->validate($this->socialRules());

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
        $this->authorizeSettingsUpdate();
        $this->validate($this->contactRules());

        $this->saveGroup([
            'contact_phone',
            'contact_email',
            'whatsapp_number',
        ], 'contact');
    }

    public function saveAi(): void
    {
        $this->authorizeSettingsUpdate();
        $this->validate([
            'data.openai_model' => ['required', 'string', Rule::in(array_keys(self::openAiModels()))],
            'data.ai_seo_enabled' => ['nullable', 'boolean'],
        ]);

        $model = (string) ($this->data['openai_model'] ?? '');

        if (! array_key_exists($model, self::openAiModels())) {
            $model = self::defaultOpenAiModel();
        }

        Setting::setValue('openai_model', $model, 'ai');
        Setting::setValue('ai_seo_enabled', ! empty($this->data['ai_seo_enabled']), 'ai');

        $this->notifySaved();
    }

    /** @return array<string, string> */
    public static function openAiModels(): array
    {
        return app(OpenAiModelPolicy::class)->seoOptions();
    }

    public static function isOpenAiConfigured(): bool
    {
        return filled(config('ai.providers.openai.key'));
    }

    private static function defaultOpenAiModel(): string
    {
        return app(OpenAiModelPolicy::class)->seoModel();
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

    private function authorizeSettingsUpdate(): void
    {
        abort_unless(auth()->user()?->can('update settings') === true, 403);
    }

    /** @return array<string, array<int, mixed>> */
    private function socialRules(): array
    {
        return [
            'data.social_facebook' => ['nullable', 'url:http,https', 'max:2048'],
            'data.social_twitter' => ['nullable', 'url:http,https', 'max:2048'],
            'data.social_instagram' => ['nullable', 'url:http,https', 'max:2048'],
            'data.social_linkedin' => ['nullable', 'url:http,https', 'max:2048'],
            'data.social_youtube' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function contactRules(): array
    {
        return [
            'data.contact_phone' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9() .-]+$/'],
            'data.contact_email' => ['nullable', 'email', 'max:255'],
            'data.whatsapp_number' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9() .-]+$/'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function websiteContentRules(): array
    {
        $rules = [];

        foreach ($this->supportedLocales() as $locale) {
            $rules["data.about_biography_{$locale}"] = ['required', 'string', 'max:3000'];
        }

        return $rules;
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
