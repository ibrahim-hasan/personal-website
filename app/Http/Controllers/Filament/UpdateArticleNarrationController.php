<?php

namespace App\Http\Controllers\Filament;

use App\Enums\ArticleNarrationStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateArticleNarrationRequest;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Services\ArticleAudio\NarrationDraftValidator;
use App\Support\Editorial\ArticleCatalog;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use UnexpectedValueException;

class UpdateArticleNarrationController extends Controller
{
    public function __invoke(
        UpdateArticleNarrationRequest $request,
        string $article,
        string $locale,
        ArticleCatalog $articles,
        ArticleNarrationScript $source,
        NarrationDraftValidator $validator,
    ): RedirectResponse {
        $resolvedArticle = $articles->findByKey($article);

        abort_if($resolvedArticle === null || ! in_array($locale, ['ar', 'en'], true), 404);

        $narration = ArticleNarration::query()
            ->where('article_key', $article)
            ->where('locale', $locale)
            ->firstOrFail();
        $sourceText = $source->build($resolvedArticle, $locale);
        $sourceHash = hash('sha256', $sourceText);

        if (! hash_equals((string) $narration->source_hash, $sourceHash)) {
            Notification::make()
                ->title(__('article_audio.notifications.narration_outdated'))
                ->warning()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        $script = trim((string) $request->validated('script'));

        try {
            $validator->validate($script, $sourceText);
        } catch (UnexpectedValueException $exception) {
            throw ValidationException::withMessages(['script' => $exception->getMessage()]);
        }

        $isApproval = $request->validated('action') === 'approve';
        $scriptChanged = ! hash_equals($narration->scriptFingerprint(), hash('sha256', $script));

        $narration->forceFill([
            'status' => $isApproval ? ArticleNarrationStatus::Approved : ArticleNarrationStatus::Draft,
            'script' => $script,
            'samples' => $scriptChanged ? [] : $narration->samples,
            'prepared_at' => $narration->prepared_at ?? now(),
            'approved_at' => $isApproval ? now() : null,
            'failed_at' => null,
            'last_error' => null,
        ])->save();

        Notification::make()
            ->title($isApproval
                ? __('article_audio.notifications.narration_approved')
                : __('article_audio.notifications.narration_saved'))
            ->success()
            ->send();

        return redirect(ManageArticleAudio::getUrl());
    }
}
