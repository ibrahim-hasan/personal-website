<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Editorial\PublishEditorialArticle;
use App\Actions\Editorial\SetEditorialArticlePublication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ArticleStateTransitionRequest;
use App\Http\Resources\Api\EditorialArticleResource;
use App\Models\Article;
use App\Services\EditorialApi\Audit;
use App\Services\EditorialApi\Concurrency;
use App\Services\EditorialApi\Idempotency;
use Illuminate\Http\JsonResponse;

class EditorialArticleStateController extends Controller
{
    public function publish(
        ArticleStateTransitionRequest $request,
        Article $article,
        PublishEditorialArticle $publishEditorialArticle,
        Concurrency $concurrency,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $article, $publishEditorialArticle, $concurrency, $audit): JsonResponse {
            $concurrency->assertCurrent($request, $article);
            $article = $publishEditorialArticle->handle($article);
            $audit->record($request, 'article.published', 'success', $article);

            return $this->response($article);
        });
    }

    public function unpublish(
        ArticleStateTransitionRequest $request,
        Article $article,
        SetEditorialArticlePublication $setPublication,
        Concurrency $concurrency,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $article, $setPublication, $concurrency, $audit): JsonResponse {
            $concurrency->assertCurrent($request, $article);
            $article = $setPublication->handle($article, false);
            $audit->record($request, 'article.unpublished', 'success', $article);

            return $this->response($article);
        });
    }

    public function archive(
        ArticleStateTransitionRequest $request,
        Article $article,
        Concurrency $concurrency,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $article, $concurrency, $audit): JsonResponse {
            $concurrency->assertCurrent($request, $article);
            $article->update(['editorial_revision' => $article->editorial_revision + 1, 'modified_at' => today()]);
            $article->delete();
            $audit->record($request, 'article.archived', 'success', $article);

            return $this->response($article);
        });
    }

    public function restore(
        ArticleStateTransitionRequest $request,
        string $article,
        Concurrency $concurrency,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $article, $concurrency, $audit): JsonResponse {
            $record = Article::withTrashed()->findOrFail($article);
            $concurrency->assertCurrent($request, $record);
            $record->restore();
            $record->update(['editorial_revision' => $record->editorial_revision + 1, 'modified_at' => today()]);
            $record = $record->fresh();
            $audit->record($request, 'article.restored', 'success', $record);

            return $this->response($record);
        });
    }

    private function response(Article $article): JsonResponse
    {
        return (new EditorialArticleResource($article))
            ->response()
            ->header('ETag', '"'.$article->editorial_revision.'"');
    }
}
