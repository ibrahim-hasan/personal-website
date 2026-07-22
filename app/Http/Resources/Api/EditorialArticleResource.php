<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditorialArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'key' => $this->key,
            'title' => $this->getTranslations('title'),
            'slug' => $this->getTranslations('slug'),
            'summary' => $this->getTranslations('summary'),
            'type' => $this->getTranslations('type'),
            'topic_keys' => $this->topic_keys,
            'is_published' => $this->is_published,
            'status' => $this->trashed() ? 'archived' : ($this->is_published ? 'published' : 'draft'),
            'revision' => $this->editorial_revision,
            'published_at' => $this->published_at?->toDateString(),
            'modified_at' => $this->modified_at?->toDateString(),
            'image_url' => $this->imageUrl(),
        ];
    }
}
