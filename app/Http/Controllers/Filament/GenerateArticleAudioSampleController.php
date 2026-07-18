<?php

namespace App\Http\Controllers\Filament;

use App\Filament\Pages\ManageArticleAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateArticleAudioSampleRequest;
use App\Jobs\GenerateArticleAudioSample;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Services\ElevenLabs\ElevenLabsTextToSpeech;
use App\Support\Editorial\ArticleCatalog;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;

class GenerateArticleAudioSampleController extends Controller
{
    public function __invoke(
        GenerateArticleAudioSampleRequest $request,
        string $article,
        string $locale,
        ArticleCatalog $articles,
        ArticleNarrationScript $source,
        ElevenLabsTextToSpeech $speech,
    ): RedirectResponse {
        $resolvedArticle = $articles->findByKey($article);

        abort_if($resolvedArticle === null || ! in_array($locale, ['ar', 'en'], true), 404);

        if (blank(config('services.elevenlabs.api_key')) || blank($speech->voiceId($locale))) {
            Notification::make()
                ->title(__('article_audio.notifications.configuration_missing'))
                ->danger()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $modelId = (string) $request->validated('model_id');
        $narration = ArticleNarration::query()
            ->where('article_key', $article)
            ->where('locale', $locale)
            ->where('source_hash', $source->fingerprint($resolvedArticle, $locale))
            ->whereNotNull('script')
            ->first();

        if ($narration === null) {
            Notification::make()
                ->title(__('article_audio.notifications.draft_required'))
                ->warning()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        if ($narration->sampleIsGenerating($modelId)) {
            Notification::make()
                ->title(__('article_audio.notifications.sample_generating'))
                ->info()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $narration->updateSample($modelId, [
            'status' => 'queued',
            'script_hash' => $narration->scriptFingerprint(),
            'queued_at' => now()->toIso8601String(),
            'failed_at' => null,
            'last_error' => null,
        ]);

        GenerateArticleAudioSample::dispatch($article, $locale, $modelId);

        Notification::make()
            ->title(__('article_audio.notifications.sample_queued'))
            ->success()
            ->send();

        return redirect(ManageArticleAudio::getUrl());
    }
}
