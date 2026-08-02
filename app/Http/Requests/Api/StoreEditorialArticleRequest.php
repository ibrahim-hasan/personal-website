<?php

namespace App\Http\Requests\Api;

use App\Support\Editorial\ArticleBody;
use Closure;
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
        $validRichDocument = function (string $attribute, mixed $value, Closure $fail): void {
            if (! app(ArticleBody::class)->isValidDocument($value)) {
                $fail("The {$attribute} field must be a valid rich-text document using supported article blocks.");
            }
        };

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
            'summary' => ['required', 'array:ar,en'],
            'summary.ar' => ['required', 'string', 'max:500'],
            'summary.en' => ['required', 'string', 'max:500'],
            'body' => ['required_without_all:lead,sections,closing', 'array:ar,en'],
            'body.ar' => ['required_with:body', 'array', $validRichDocument],
            'body.en' => ['required_with:body', 'array', $validRichDocument],
            'lead' => ['required_without:body', 'array:ar,en'],
            'lead.ar' => ['required_with:lead', 'string'],
            'lead.en' => ['required_with:lead', 'string'],
            'sections' => ['required_without:body', 'array:ar,en'],
            'sections.ar' => ['required_with:sections', 'array', 'min:1'],
            'sections.en' => ['required_with:sections', 'array', 'min:1'],
            'sections.*.*.heading' => ['required', 'string', 'max:180'],
            'sections.*.*.paragraphs' => ['required', 'array', 'min:1'],
            'sections.*.*.paragraphs.*' => ['required', 'string'],
            'sections.*.*.points' => ['nullable', 'array'],
            'sections.*.*.points.*' => ['nullable', 'string', 'max:500'],
            'sections.*.*.note' => ['nullable', 'string'],
            'closing' => ['required_without:body', 'array:ar,en'],
            'closing.ar' => ['required_with:closing', 'string'],
            'closing.en' => ['required_with:closing', 'string'],
            'image_alt' => ['required', 'array:ar,en'],
            'image_alt.ar' => ['required', 'string', 'max:250'],
            'image_alt.en' => ['required', 'string', 'max:250'],
            'image_caption' => ['sometimes', 'array:ar,en'],
            'image_caption.ar' => ['nullable', 'string', 'max:500'],
            'image_caption.en' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['required', 'array:ar,en'],
            'seo_title.ar' => ['required', 'string', 'max:60'],
            'seo_title.en' => ['required', 'string', 'max:60'],
            'seo_description' => ['required', 'array:ar,en'],
            'seo_description.ar' => ['required', 'string', 'max:155'],
            'seo_description.en' => ['required', 'string', 'max:155'],
            'topic_keys' => ['required', 'array', 'min:1', 'max:30'],
            'topic_keys.*' => ['required', 'string', 'max:80', 'distinct'],
            'service_keys' => ['sometimes', 'array', 'max:30'],
            'service_keys.*' => ['required', 'string', 'max:80', 'distinct'],
            'project_keys' => ['sometimes', 'array', 'max:30'],
            'project_keys.*' => ['required', 'string', 'max:80', 'distinct'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
