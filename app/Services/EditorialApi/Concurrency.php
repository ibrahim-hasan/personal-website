<?php

namespace App\Services\EditorialApi;

use App\Models\Article;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class Concurrency
{
    public function assertCurrent(Request $request, Article $article): void
    {
        $revision = trim((string) $request->header('If-Match'), '"');

        if ($revision === '' || ! ctype_digit($revision) || (int) $revision !== $article->editorial_revision) {
            throw new HttpResponseException(response()->json([
                'message' => 'Revision conflict. Retrieve the current article and try again.',
                'errors' => [
                    'If-Match' => ['The supplied revision is no longer current.'],
                ],
            ], 409));
        }
    }

    public function next(Article $article): int
    {
        return $article->editorial_revision + 1;
    }
}
