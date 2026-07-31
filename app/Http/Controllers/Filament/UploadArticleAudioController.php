<?php

namespace App\Http\Controllers\Filament;

use App\Filament\Pages\ManageArticleAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadArticleAudioRequest;
use App\Services\ArticleAudio\StoreUploadedArticleAudio;
use App\Support\Editorial\ArticleCatalog;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class UploadArticleAudioController extends Controller
{
    public function __invoke(
        UploadArticleAudioRequest $request,
        string $article,
        string $locale,
        ArticleCatalog $articles,
        StoreUploadedArticleAudio $storeUploadedArticleAudio,
    ): RedirectResponse {
        $resolvedArticle = $articles->findByKey($article);

        abort_if($resolvedArticle === null || ! in_array($locale, ['ar', 'en'], true), 404);

        /** @var UploadedFile $file */
        $file = $request->file('audio');
        $audio = $storeUploadedArticleAudio->handle(
            $resolvedArticle,
            $locale,
            $file,
            $request->user()?->getKey(),
        );

        if ($audio === null) {
            Notification::make()
                ->title(__('article_audio.notifications.already_generating'))
                ->info()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        Notification::make()
            ->title(__('article_audio.notifications.uploaded'))
            ->success()
            ->send();

        return redirect(ManageArticleAudio::getUrl());
    }
}
