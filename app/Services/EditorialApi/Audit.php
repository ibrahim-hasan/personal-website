<?php

namespace App\Services\EditorialApi;

use App\Models\Article;
use App\Models\EditorialApiAuditLog;
use Illuminate\Http\Request;

class Audit
{
    public function record(Request $request, string $action, string $outcome, ?Article $article = null): void
    {
        $token = $request->attributes->get('editorial_api_token');

        EditorialApiAuditLog::query()->create([
            'client_id' => $request->attributes->get('editorial_api_client')?->getKey(),
            'user_id' => filled($token?->oauth_user_id) ? $token->oauth_user_id : null,
            'article_id' => $article?->getKey(),
            'request_id' => $request->attributes->get('editorial_api_request_id'),
            'action' => $action,
            'outcome' => $outcome,
            'ip_hash' => $request->ip() === null ? null : hash('sha256', $request->ip()),
            'occurred_at' => now(),
        ]);
    }
}
