<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEditorialArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'alpha_dash', 'max:80', Rule::unique('articles', 'key')],
            'title' => ['required', 'array:ar,en'],
            'title.ar' => ['required', 'string', 'max:180'],
            'title.en' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'array:ar,en'],
            'slug.ar' => ['required', 'string', 'max:180', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique('articles', 'slug->ar')],
            'slug.en' => ['required', 'string', 'max:180', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique('articles', 'slug->en')],
            'type' => ['required', 'array:ar,en'],
            'type.ar' => ['required', 'string', 'max:80'],
            'type.en' => ['required', 'string', 'max:80'],
            'read_minutes' => ['required', 'array:ar,en'],
            'read_minutes.ar' => ['required', 'integer', 'between:1,120'],
            'read_minutes.en' => ['required', 'integer', 'between:1,120'],
            'summary' => ['required', 'array:ar,en'],
            'summary.ar' => ['required', 'string', 'max:500'],
            'summary.en' => ['required', 'string', 'max:500'],
            'lead' => ['required', 'array:ar,en'],
            'lead.ar' => ['required', 'string'],
            'lead.en' => ['required', 'string'],
            'sections' => ['required', 'array:ar,en'],
            'sections.ar' => ['required', 'array', 'min:1'],
            'sections.en' => ['required', 'array', 'min:1'],
            'sections.*.*.heading' => ['required', 'string', 'max:180'],
            'sections.*.*.paragraphs' => ['required', 'array', 'min:1'],
            'sections.*.*.paragraphs.*' => ['required', 'string'],
            'sections.*.*.points' => ['nullable', 'array'],
            'sections.*.*.points.*' => ['nullable', 'string', 'max:500'],
            'sections.*.*.note' => ['nullable', 'string'],
            'closing' => ['required', 'array:ar,en'],
            'closing.ar' => ['required', 'string'],
            'closing.en' => ['required', 'string'],
            'seo_title' => ['required', 'array:ar,en'],
            'seo_title.ar' => ['required', 'string', 'max:70'],
            'seo_title.en' => ['required', 'string', 'max:70'],
            'seo_description' => ['required', 'array:ar,en'],
            'seo_description.ar' => ['required', 'string', 'max:170'],
            'seo_description.en' => ['required', 'string', 'max:170'],
            'topic_keys' => ['required', 'array', 'min:1', 'max:30'],
            'topic_keys.*' => ['required', 'string', 'max:80', 'distinct'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
