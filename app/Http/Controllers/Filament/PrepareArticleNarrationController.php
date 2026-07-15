<?php

namespace App\Http\Controllers\Filament;

use App\Enums\ArticleNarrationStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\PrepareArticleNarrationRequest;
use App\Jobs\PrepareArticleNarration;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;

class PrepareArticleNarrationController extends Controller
{
    public function __invoke(
        PrepareArticleNarrationRequest $request,
        string $article,
        string $locale,
        ArticleCatalog $articles,
        ArticleNarrationScript $source,
    ): RedirectResponse {
        $resolvedArticle = $articles->findByKey($article);

        abort_if($resolvedArticle === null || ! in_array($locale, ['ar', 'en'], true), 404);

        if (blank(config('services.openai.api_key'))) {
            Notification::make()
                ->title(__('article_audio.notifications.openai_missing'))
                ->danger()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $narration = ArticleNarration::query()->firstOrNew([
            'article_key' => $article,
            'locale' => $locale,
        ]);

        if ($narration->exists && $narration->isPreparing()) {
            Notification::make()
                ->title(__('article_audio.notifications.already_preparing'))
                ->info()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        if (! $narration->exists) {
            $narration->source_hash = $source->fingerprint($resolvedArticle, $locale);
        }

        $narration->forceFill([
            'requested_by_user_id' => $request->user()?->getKey(),
            'status' => ArticleNarrationStatus::Queued,
            'preparation_started_at' => null,
            'failed_at' => null,
            'last_error' => null,
        ])->save();

        PrepareArticleNarration::dispatch($article, $locale);

        Notification::make()
            ->title(__('article_audio.notifications.preparation_queued'))
            ->success()
            ->send();

        return redirect(ManageArticleAudio::getUrl());
    }
}
