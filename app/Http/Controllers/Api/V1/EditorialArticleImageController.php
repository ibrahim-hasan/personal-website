<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ArticleStateTransitionRequest;
use App\Http\Requests\Api\UploadEditorialArticleImageRequest;
use App\Http\Resources\Api\EditorialArticleResource;
use App\Models\Article;
use App\Services\EditorialApi\Audit;
use App\Services\EditorialApi\Concurrency;
use App\Services\EditorialApi\Idempotency;
use Illuminate\Http\JsonResponse;

class EditorialArticleImageController extends Controller
{
    public function store(
        UploadEditorialArticleImageRequest $request,
        Article $article,
        Concurrency $concurrency,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $article, $concurrency, $audit): JsonResponse {
            $concurrency->assertCurrent($request, $article);
            $article->addMediaFromRequest('image')->toMediaCollection(Article::IMAGE_COLLECTION);
            $article->update(['editorial_revision' => $article->editorial_revision + 1, 'modified_at' => today()]);
            $article = $article->refresh();
            $audit->record($request, 'article.image_uploaded', 'success', $article);

            return $this->response($article);
        });
    }

    public function destroy(
        ArticleStateTransitionRequest $request,
        Article $article,
        Concurrency $concurrency,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $article, $concurrency, $audit): JsonResponse {
            $concurrency->assertCurrent($request, $article);
            $article->clearMediaCollection(Article::IMAGE_COLLECTION);
            $article->update(['editorial_revision' => $article->editorial_revision + 1, 'modified_at' => today()]);
            $article = $article->refresh();
            $audit->record($request, 'article.image_removed', 'success', $article);

            return $this->response($article);
        });
    }

    private function response(Article $article): JsonResponse
    {
        return (new EditorialArticleResource($article))
            ->response()
            ->header('ETag', '"'.$article->editorial_revision.'"');
    }
}
