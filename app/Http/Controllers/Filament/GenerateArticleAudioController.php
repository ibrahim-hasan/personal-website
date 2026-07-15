<?php

namespace App\Http\Controllers\Filament;

use App\Enums\ArticleAudioStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateArticleAudioRequest;
use App\Jobs\GenerateArticleAudio;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Support\Editorial\ArticleCatalog;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;

class GenerateArticleAudioController extends Controller
{
    public function __invoke(
        GenerateArticleAudioRequest $request,
        string $article,
        string $locale,
        ArticleCatalog $articles,
        ArticleAudioScript $scripts,
    ): RedirectResponse {
        $resolvedArticle = $articles->findByKey($article);

        abort_if($resolvedArticle === null || ! in_array($locale, ['ar', 'en'], true), 404);

        if (blank(config('services.elevenlabs.api_key')) || blank(config('services.elevenlabs.voice_id'))) {
            Notification::make()
                ->title(__('article_audio.notifications.configuration_missing'))
                ->body(__('article_audio.notifications.configuration_missing_body'))
                ->danger()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $modelId = (string) $request->validated('model_id');
        $script = $scripts->approved($resolvedArticle, $locale, $modelId);

        if ($script === null) {
            Notification::make()
                ->title(__('article_audio.notifications.narration_required'))
                ->warning()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $narration = ArticleNarration::query()->find($script->narrationId);

        if ($narration === null || ! $narration->hasCurrentSample($modelId)) {
            Notification::make()
                ->title(__('article_audio.notifications.sample_required'))
                ->warning()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $audio = ArticleAudio::query()->firstOrCreate(
            ['article_key' => $article, 'locale' => $locale],
            ['status' => ArticleAudioStatus::Queued, 'queued_at' => now()],
        );

        if (! $audio->wasRecentlyCreated && $audio->isGenerating()) {
            Notification::make()
                ->title(__('article_audio.notifications.already_generating'))
                ->info()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $audio->forceFill([
            'requested_by_user_id' => $request->user()?->getKey(),
            'status' => ArticleAudioStatus::Queued,
            'content_hash' => $script->contentHash,
            'model_id' => $modelId,
            'queued_at' => now(),
            'generation_started_at' => null,
            'failed_at' => null,
            'last_error' => null,
        ])->save();

        GenerateArticleAudio::dispatch($article, $locale, $modelId);

        Notification::make()
            ->title(__('article_audio.notifications.queued'))
            ->body(__('article_audio.notifications.queued_body'))
            ->success()
            ->send();

        return redirect(ManageArticleAudio::getUrl());
    }
}
