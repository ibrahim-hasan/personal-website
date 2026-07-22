<?php

use App\Http\Controllers\Api\V1\EditorialArticleController;
use App\Http\Controllers\Api\V1\EditorialArticleImageController;
use App\Http\Controllers\Api\V1\EditorialArticleStateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/articles')->group(function (): void {
    Route::get('/', [EditorialArticleController::class, 'index'])
        ->middleware(['article-scope:articles:read', 'throttle:editorial-api-read']);
    Route::post('/', [EditorialArticleController::class, 'store'])
        ->middleware(['article-scope:articles:write', 'idempotency', 'throttle:editorial-api-write']);
    Route::get('/{article:id}', [EditorialArticleController::class, 'show'])
        ->middleware(['article-scope:articles:read', 'throttle:editorial-api-read']);
    Route::patch('/{article:id}', [EditorialArticleController::class, 'update'])
        ->middleware(['article-scope:articles:write', 'idempotency', 'throttle:editorial-api-write']);
    Route::put('/{article:id}/image', [EditorialArticleImageController::class, 'store'])
        ->middleware(['article-scope:media:write', 'idempotency', 'throttle:editorial-api-upload']);
    Route::delete('/{article:id}/image', [EditorialArticleImageController::class, 'destroy'])
        ->middleware(['article-scope:media:write', 'idempotency', 'throttle:editorial-api-upload']);
    Route::post('/{article:id}/publish', [EditorialArticleStateController::class, 'publish'])
        ->middleware(['article-scope:articles:publish', 'idempotency', 'throttle:editorial-api-write']);
    Route::post('/{article:id}/unpublish', [EditorialArticleStateController::class, 'unpublish'])
        ->middleware(['article-scope:articles:publish', 'idempotency', 'throttle:editorial-api-write']);
    Route::delete('/{article:id}', [EditorialArticleStateController::class, 'archive'])
        ->middleware(['article-scope:articles:archive', 'idempotency', 'throttle:editorial-api-write']);
    Route::post('/{article}/restore', [EditorialArticleStateController::class, 'restore'])
        ->middleware(['article-scope:articles:archive', 'idempotency', 'throttle:editorial-api-write']);
});
