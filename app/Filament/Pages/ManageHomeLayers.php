<?php

namespace App\Filament\Pages;

use App\Filament\Components\TranslatableTabs;
use App\Models\Setting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageHomeLayers extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.manage-home-layers';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('Home Layers');
    }

    public function getTitle(): string
    {
        return __('Home Layers');
    }

    public function mount(): void
    {
        $layers = setting_value('home_layers', 'home', $this->defaultHomeLayers());

        $this->form->fill([
            'layers' => is_array($layers) ? $layers : $this->defaultHomeLayers(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('translatable.locales', ['ar', 'en']);

        $translationsTabsSchema = [];

        foreach ($locales as $locale) {
            $translationsTabsSchema[$locale] = [
                TextInput::make("title.{$locale}")
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255),
                Textarea::make("description.{$locale}")
                    ->label(__('Description'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ];
        }

        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Home Layers'))
                    ->description(__('Manage the five-layer accordion content on the home page with sortable translatable items.'))
                    ->schema([
                        Repeater::make('layers')
                            ->label(__('Layers'))
                            ->required()
                            ->minItems(5)
                            ->maxItems(5)
                            ->default($this->defaultHomeLayers())
                            ->reorderable()
                            ->addable(false)
                            ->deletable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => data_get($state, 'title.'.app()->getLocale()) ?: data_get($state, 'title.en'))
                            ->schema([
                                TranslatableTabs::make($translationsTabsSchema, columns: 1),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setValue('home_layers', $state['layers'] ?? $this->defaultHomeLayers(), 'home');

        Notification::make()
            ->title(__('Settings saved successfully'))
            ->success()
            ->send();
    }

    /**
     * @return array<int, array{title: array<string, string>, description: array<string, string>}>
     */
    private function defaultHomeLayers(): array
    {
        return [
            [
                'title' => [
                    'ar' => 'طبقة تشخيص المنتج',
                    'en' => 'Product Diagnosis Layer',
                ],
                'description' => [
                    'ar' => 'تحديد السطح الحقيقي للمشكلة: المستخدم، المتصفح، السجل، الطلب، والمسار الذي كسر التجربة.',
                    'en' => 'Identify the real surface of the problem: user, browser, logs, request path, and the workflow that broke.',
                ],
            ],
            [
                'title' => [
                    'ar' => 'طبقة بنية المنصة',
                    'en' => 'Platform Architecture Layer',
                ],
                'description' => [
                    'ar' => 'تنظيم Laravel وDjango ولوحات الإدارة والوظائف الخلفية حول عقود واضحة وسهلة الصيانة.',
                    'en' => 'Shape Laravel, Django, admin panels, and background jobs around clear, maintainable contracts.',
                ],
            ],
            [
                'title' => [
                    'ar' => 'طبقة موثوقية الذكاء الاصطناعي',
                    'en' => 'AI Reliability Layer',
                ],
                'description' => [
                    'ar' => 'اختبار الاسترجاع، اختيار الأدلة، صياغة الإجابات، وسلوك الواجهة قبل اعتبار المساعد صالحاً للاستخدام.',
                    'en' => 'Test retrieval, evidence selection, answer shaping, and widget behavior before calling an assistant usable.',
                ],
            ],
            [
                'title' => [
                    'ar' => 'طبقة تجربة الإدارة',
                    'en' => 'Admin Experience Layer',
                ],
                'description' => [
                    'ar' => 'جعل لوحات Filament وأدوات التشغيل عملية، واضحة، وسريعة لمن يستخدمها يومياً.',
                    'en' => 'Make Filament panels and operating tools practical, clear, and fast for daily use.',
                ],
            ],
            [
                'title' => [
                    'ar' => 'طبقة التحقق والنشر',
                    'en' => 'Verification And Release Layer',
                ],
                'description' => [
                    'ar' => 'إغلاق العمل باختبارات، بناء أصول، وفحص المتصفح أو المسار الحي الذي يثبت أن الإصلاح وصل.',
                    'en' => 'Close the work with tests, asset builds, and browser or live-path checks that prove the fix landed.',
                ],
            ],
        ];
    }
}
