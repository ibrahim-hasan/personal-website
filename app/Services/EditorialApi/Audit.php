<?php

namespace App\Services\EditorialApi;

use App\Models\Article;
use App\Models\EditorialApiAuditLog;
use Illuminate\Http\Request;

class Audit
{
    public function __construct(private readonly EditorialArticleRelations $relations) {}

    public function record(Request $request, string $action, string $outcome, ?Article $article = null): void
    {
        $token = $request->attributes->get('editorial_api_token');
        $clientId = $request->attributes->get('editorial_api_client')?->getKey();
        $userId = $token?->oauth_user_id;

        if ($article !== null) {
            $this->relations->captureRevisionSnapshot($article, $action);
        }

        EditorialApiAuditLog::query()->create([
            'client_id' => $clientId,
            'user_id' => filled($userId) && (string) $userId !== (string) $clientId ? $userId : null,
            'article_id' => $article?->getKey(),
            'related_content_keys' => $article === null ? null : $this->relations->auditRepresentation($article),
            'request_id' => $request->attributes->get('editorial_api_request_id'),
            'action' => $action,
            'outcome' => $outcome,
            'ip_hash' => $request->ip() === null ? null : hash('sha256', $request->ip()),
            'occurred_at' => now(),
        ]);
    }
}
