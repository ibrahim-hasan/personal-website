<?php

namespace App\Mcp\Tools;

use App\Models\Article;
use App\Services\EditorialApi\Concurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Replace an article hero image with a base64 encoded JPEG, PNG, WebP, or AVIF image.')]
class UploadEditorialImageTool extends EditorialTool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $this->requireScope('media:write');
        $input = $request->validate([
            'id' => ['required', 'integer'],
            'revision' => ['required', 'integer', 'min:1'],
            'image_base64' => ['required', 'string'],
        ]);
        $article = Article::query()->findOrFail($input['id']);
        request()->headers->set('If-Match', (string) $input['revision']);
        app(Concurrency::class)->assertCurrent(request(), $article);
        $encoded = preg_replace('/^data:[^;]+;base64,/', '', $input['image_base64']);

        if (! is_string($encoded) || strlen($encoded) > 11_200_000) {
            throw ValidationException::withMessages(['image_base64' => ['The image may not be greater than 8 MB.']]);
        }

        $article->addMediaFromBase64($encoded, 'image/jpeg', 'image/png', 'image/webp', 'image/avif')
            ->toMediaCollection(Article::IMAGE_COLLECTION);
        $article->update(['editorial_revision' => $article->editorial_revision + 1, 'modified_at' => today()]);
        $this->audit('article.image_uploaded', $article);

        return $this->articleResponse($article->refresh());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'revision' => $schema->integer()->min(1)->required(),
            'image_base64' => $schema->string()->description('Base64 image data, optionally with a data URI prefix.')->required(),
        ];
    }
}
