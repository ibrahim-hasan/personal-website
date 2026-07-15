<?php

namespace App\Filament\Pages;

use App\Enums\ArticleAudioStatus;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ManageArticleAudio extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-speaker-wave';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.manage-article-audio';

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
        return auth()->user()?->can('view_any intellectual_libraries') === true;
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
        $openAiKeyConfigured = filled(config('services.openai.api_key'));
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
        return auth()->user()?->can('update intellectual_libraries') === true;
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

        if (str_contains($error, 'HTTP 402') || str_contains($error, 'insufficient_credits')) {
            return __('article_audio.errors.insufficient_credits');
        }

        if (str_contains($error, 'missing_permissions')) {
            return __('article_audio.errors.missing_permissions');
        }

        return $error;
    }
}
