<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Editorial\CreateEditorialArticle;
use App\Actions\Editorial\UpdateEditorialArticle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEditorialArticleRequest;
use App\Http\Requests\Api\UpdateEditorialArticleRequest;
use App\Http\Resources\Api\EditorialArticleResource;
use App\Models\Article;
use App\Services\EditorialApi\Audit;
use App\Services\EditorialApi\Concurrency;
use App\Services\EditorialApi\Idempotency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EditorialArticleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->string('status')->toString();
        $query = $status === 'archived' ? Article::onlyTrashed() : Article::query();

        $articles = $query
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->orderByDesc('id')
            ->cursorPaginate(min($request->integer('per_page', 25), 100));

        return EditorialArticleResource::collection($articles);
    }

    public function store(
        StoreEditorialArticleRequest $request,
        CreateEditorialArticle $createEditorialArticle,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $createEditorialArticle, $audit): JsonResponse {
            $article = $createEditorialArticle->handle($request->validated());
            $audit->record($request, 'article.created', 'success', $article);

            return $this->response($article, 201);
        });
    }

    public function show(Article $article): JsonResponse
    {
        return $this->response($article);
    }

    public function update(
        UpdateEditorialArticleRequest $request,
        Article $article,
        UpdateEditorialArticle $updateEditorialArticle,
        Concurrency $concurrency,
        Idempotency $idempotency,
        Audit $audit,
    ): JsonResponse {
        return $idempotency->execute($request, function () use ($request, $article, $updateEditorialArticle, $concurrency, $audit): JsonResponse {
            $concurrency->assertCurrent($request, $article);
            $article = $updateEditorialArticle->handle($article, $request->validated());
            $audit->record($request, 'article.updated', 'success', $article);

            return $this->response($article);
        });
    }

    private function response(Article $article, int $status = 200): JsonResponse
    {
        return (new EditorialArticleResource($article))
            ->response()
            ->setStatusCode($status)
            ->header('ETag', '"'.$article->editorial_revision.'"');
    }
}
