<?php

namespace App\Filament\Pages;

use App\Enums\ArticleAudioStatus;
use App\Enums\ArticleNarrationStatus;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Ai\ElevenLabsExecutionBudget;
use App\Support\Editorial\ArticleCatalog;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;

class ManageArticleAudio extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-speaker-wave';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.manage-article-audio';

    #[Locked]
    public bool $activeWork = false;

    #[Locked]
    public bool $observedActiveWork = false;

    /**
     * @var list<array{article_key: string, locale: string, source_hash: string}>
     */
    #[Locked]
    public array $workTargets = [];

    public function mount(): void
    {
        $this->workTargets = $this->buildWorkTargets();
        $this->activeWork = $this->detectActiveWork();
        $this->observedActiveWork = $this->activeWork;
    }

    public function pollWorkStatus(): void
    {
        $this->activeWork = $this->detectActiveWork();
        $this->observedActiveWork = $this->observedActiveWork || $this->activeWork;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('article_audio.page.navigation');
    }

    public function getTitle(): string
    {
        return __('article_audio.page.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any articles') === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /** @return list<array<string, mixed>> */
    public function articleRows(): array
    {
        $articles = app(ArticleCatalog::class);
        $source = app(ArticleNarrationScript::class);
        $scripts = app(ArticleAudioScript::class);
        $tracks = ArticleAudio::query()->get()->keyBy(
            fn (ArticleAudio $audio): string => $audio->article_key.':'.$audio->locale,
        );
        $narrations = ArticleNarration::query()->get()->keyBy(
            fn (ArticleNarration $narration): string => $narration->article_key.':'.$narration->locale,
        );
        $models = (array) config('services.elevenlabs.models', []);
        $rows = [];

        foreach ($articles->all() as $article) {
            foreach (['ar', 'en'] as $locale) {
                $key = $article->key.':'.$locale;
                /** @var ArticleAudio|null $track */
                $track = $tracks->get($key);
                /** @var ArticleNarration|null $narration */
                $narration = $narrations->get($key);
                $sourceHash = $source->fingerprint($article, $locale);
                $narrationIsCurrent = $narration !== null
                    && hash_equals((string) $narration->source_hash, $sourceHash);
                $trackModel = $track?->model_id ?: (string) config('services.elevenlabs.model_id');
                $contentHash = $scripts->publicFingerprint($article, $locale, $trackModel);
                $isStale = $track !== null && $track->isStale($contentHash);
                $pathExists = $track?->path !== null
                    && Storage::disk($track->disk)->exists($track->path);
                $isCurrent = $track?->isReady() === true && ! $isStale && $pathExists;
                $modelRows = [];

                foreach ($models as $modelId => $profile) {
                    $sample = $narrationIsCurrent ? $narration?->sample((string) $modelId) : null;
                    $sampleUrl = $narrationIsCurrent ? $narration?->sampleUrl((string) $modelId) : null;
                    $sampleIsGenerating = $narrationIsCurrent
                        && $narration?->sampleIsGenerating((string) $modelId) === true;
                    $sampleIsCurrent = $sampleUrl !== null;

                    $modelRows[] = [
                        'id' => (string) $modelId,
                        'label' => (string) data_get($profile, 'label', $modelId),
                        'sample' => $sample,
                        'sample_url' => $sampleUrl,
                        'sample_status_label' => $this->sampleStatusLabel($narration, $sample, $sampleIsCurrent),
                        'sample_status_color' => $this->sampleStatusColor($sample, $sampleIsCurrent),
                        'is_sample_generating' => $sampleIsGenerating,
                        'can_generate_full' => $narrationIsCurrent
                            && $narration?->isApprovedFor($sourceHash) === true
                            && $sampleIsCurrent,
                    ];
                }

                $rows[] = [
                    'key' => $article->key,
                    'locale' => $locale,
                    'locale_label' => __('article_audio.locales.'.$locale),
                    'title' => $article->localized($locale)['title'],
                    'track' => $track,
                    'status_label' => $this->trackStatusLabel($track, $isStale),
                    'status_color' => $this->trackStatusColor($track, $isStale),
                    'is_current' => $isCurrent,
                    'is_stale' => $isStale,
                    'is_generating' => $track?->isGenerating() === true,
                    'audio_url' => $isCurrent ? $track?->publicUrl() : null,
                    'error_message' => $this->trackErrorMessage($track),
                    'narration' => $narration,
                    'narration_is_current' => $narrationIsCurrent,
                    'narration_is_approved' => $narrationIsCurrent && $narration?->isApprovedFor($sourceHash) === true,
                    'narration_status_label' => $this->narrationStatusLabel($narration, $narrationIsCurrent),
                    'narration_status_color' => $this->narrationStatusColor($narration, $narrationIsCurrent),
                    'is_preparing' => $narration?->isPreparing() === true,
                    'models' => $modelRows,
                    'has_active_work' => $track?->isGenerating() === true
                        || $narration?->isPreparing() === true
                        || collect($modelRows)->contains('is_sample_generating', true),
                ];
            }
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    public function configuration(): array
    {
        $voiceId = (string) config('services.elevenlabs.voice_id');
        $elevenLabsKeyConfigured = filled(config('services.elevenlabs.api_key'));
        $openAiKeyConfigured = filled(config('ai.providers.openai.key'));
        $voiceConfigured = $voiceId !== '';

        return [
            'ready' => $elevenLabsKeyConfigured && $voiceConfigured && $openAiKeyConfigured,
            'preparation_ready' => $openAiKeyConfigured,
            'synthesis_ready' => $elevenLabsKeyConfigured && $voiceConfigured,
            'openai_key_configured' => $openAiKeyConfigured,
            'api_key_configured' => $elevenLabsKeyConfigured,
            'voice_configured' => $voiceConfigured,
            'voice_label' => $this->maskVoiceId($voiceId),
            'preparation_model' => (string) config('services.openai.narration_model'),
            'model' => (string) config('services.elevenlabs.model_id'),
            'output_format' => (string) config('services.elevenlabs.output_format'),
            'models' => (array) config('services.elevenlabs.models', []),
        ];
    }

    public function canGenerate(): bool
    {
        return auth()->user()?->can('update articles') === true;
    }

    private function detectActiveWork(): bool
    {
        if ($this->workTargets === []) {
            return false;
        }

        $audioQuery = ArticleAudio::query();
        $this->constrainToActiveTargets($audioQuery, $this->workTargets);

        $stalledAt = now()->subSeconds(ElevenLabsExecutionBudget::stalledAfterSeconds());
        $audioIsGenerating = $audioQuery
            ->where(function (Builder $query) use ($stalledAt): void {
                $query
                    ->where(function (Builder $query) use ($stalledAt): void {
                        $query
                            ->where('status', ArticleAudioStatus::Queued->value)
                            ->where(function (Builder $query) use ($stalledAt): void {
                                $query
                                    ->where('queued_at', '>=', $stalledAt)
                                    ->orWhere(function (Builder $query) use ($stalledAt): void {
                                        $query
                                            ->whereNull('queued_at')
                                            ->where('updated_at', '>=', $stalledAt);
                                    });
                            });
                    })
                    ->orWhere(function (Builder $query) use ($stalledAt): void {
                        $query
                            ->where('status', ArticleAudioStatus::Processing->value)
                            ->where(function (Builder $query) use ($stalledAt): void {
                                $query
                                    ->where('generation_started_at', '>=', $stalledAt)
                                    ->orWhere(function (Builder $query) use ($stalledAt): void {
                                        $query
                                            ->whereNull('generation_started_at')
                                            ->where('updated_at', '>=', $stalledAt);
                                    });
                            });
                    });
            })
            ->exists();

        if ($audioIsGenerating) {
            return true;
        }

        $narrationQuery = ArticleNarration::query();
        $this->constrainToActiveTargets($narrationQuery, $this->workTargets, includeSourceHash: true);

        $preparationStalledAt = now()->subMinutes(10);
        $configuredModelIds = array_map('strval', array_keys((array) config('services.elevenlabs.models', [])));

        return $narrationQuery
            ->where(function (Builder $query) use ($configuredModelIds, $preparationStalledAt): void {
                $query->where(function (Builder $query) use ($preparationStalledAt): void {
                    $query
                        ->whereIn('status', [
                            ArticleNarrationStatus::Queued->value,
                            ArticleNarrationStatus::Preparing->value,
                        ])
                        ->where(function (Builder $query) use ($preparationStalledAt): void {
                            $query
                                ->where('preparation_started_at', '>=', $preparationStalledAt)
                                ->orWhere(function (Builder $query) use ($preparationStalledAt): void {
                                    $query
                                        ->whereNull('preparation_started_at')
                                        ->where('updated_at', '>=', $preparationStalledAt);
                                });
                        });
                });

                foreach ($configuredModelIds as $modelId) {
                    $query->orWhereIn("samples->{$modelId}->status", ['queued', 'processing']);
                }
            })
            ->exists();
    }

    /**
     * @return list<array{article_key: string, locale: string, source_hash: string}>
     */
    private function buildWorkTargets(): array
    {
        $source = app(ArticleNarrationScript::class);
        $targets = [];

        foreach (app(ArticleCatalog::class)->all() as $article) {
            foreach (['ar', 'en'] as $locale) {
                $targets[] = [
                    'article_key' => $article->key,
                    'locale' => $locale,
                    'source_hash' => $source->fingerprint($article, $locale),
                ];
            }
        }

        return $targets;
    }

    /**
     * @param  list<array{article_key: string, locale: string, source_hash: string}>  $targets
     */
    private function constrainToActiveTargets(Builder $query, array $targets, bool $includeSourceHash = false): void
    {
        $query->where(function (Builder $query) use ($targets, $includeSourceHash): void {
            foreach ($targets as $target) {
                $query->orWhere(function (Builder $query) use ($target, $includeSourceHash): void {
                    $query
                        ->where('article_key', $target['article_key'])
                        ->where('locale', $target['locale']);

                    if ($includeSourceHash) {
                        $query->where('source_hash', $target['source_hash']);
                    }
                });
            }
        });
    }

    private function trackStatusLabel(?ArticleAudio $track, bool $isStale): string
    {
        if ($track === null) {
            return __('article_audio.status.not_generated');
        }

        if ($track->isGenerationStalled()) {
            return __('article_audio.status.stalled');
        }

        if ($isStale && $track->status === ArticleAudioStatus::Ready) {
            return __('article_audio.status.stale');
        }

        return $track->status->label();
    }

    private function trackStatusColor(?ArticleAudio $track, bool $isStale): string
    {
        if ($isStale || $track?->isGenerationStalled()) {
            return 'warning';
        }

        return match ($track?->status) {
            ArticleAudioStatus::Queued, ArticleAudioStatus::Processing => 'info',
            ArticleAudioStatus::Ready => 'success',
            ArticleAudioStatus::Failed => 'danger',
            default => 'gray',
        };
    }

    private function narrationStatusLabel(?ArticleNarration $narration, bool $isCurrent): string
    {
        if ($narration === null) {
            return __('article_audio.narration_status.not_prepared');
        }

        if ($narration->isPreparationStalled()) {
            return __('article_audio.narration_status.stalled');
        }

        if (! $isCurrent) {
            return __('article_audio.narration_status.stale');
        }

        return $narration->status->label();
    }

    private function narrationStatusColor(?ArticleNarration $narration, bool $isCurrent): string
    {
        if (! $isCurrent || $narration?->isPreparationStalled()) {
            return 'warning';
        }

        return match ($narration?->status?->value) {
            'queued', 'preparing' => 'info',
            'approved' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    /** @param array<string, mixed>|null $sample */
    private function sampleStatusLabel(?ArticleNarration $narration, ?array $sample, bool $isCurrent): string
    {
        if ($sample === null) {
            return __('article_audio.sample_status.not_generated');
        }

        if (! $isCurrent && data_get($sample, 'status') === 'ready') {
            return __('article_audio.sample_status.stale');
        }

        return __('article_audio.sample_status.'.data_get($sample, 'status', 'not_generated'));
    }

    /** @param array<string, mixed>|null $sample */
    private function sampleStatusColor(?array $sample, bool $isCurrent): string
    {
        if (! $isCurrent && data_get($sample, 'status') === 'ready') {
            return 'warning';
        }

        return match (data_get($sample, 'status')) {
            'queued', 'processing' => 'info',
            'ready' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    private function maskVoiceId(string $voiceId): string
    {
        if ($voiceId === '') {
            return __('article_audio.configuration.not_configured');
        }

        if (mb_strlen($voiceId) <= 10) {
            return str_repeat('•', mb_strlen($voiceId));
        }

        return mb_substr($voiceId, 0, 4).'…'.mb_substr($voiceId, -4);
    }

    private function trackErrorMessage(?ArticleAudio $track): ?string
    {
        $error = $track?->last_error;

        if (blank($error)) {
            return null;
        }

        if (str_contains($error, 'HTTP 402') || str_contains($error, 'insufficient_credits') || str_contains($error, 'quota_exceeded')) {
            return __('article_audio.errors.insufficient_credits');
        }

        if (str_contains($error, 'missing_permissions')) {
            return __('article_audio.errors.missing_permissions');
        }

        return $error;
    }
}
