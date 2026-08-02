<?php

namespace App\Http\Requests\Api;

use App\Support\Editorial\ArticleBody;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditorialArticleRequest extends FormRequest
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
            'title' => ['sometimes', 'array:ar,en'],
            'title.ar' => ['required_with:title', 'string', 'max:180'],
            'title.en' => ['required_with:title', 'string', 'max:180'],
            'slug' => ['sometimes', 'array:ar,en'],
            'slug.ar' => ['required_with:slug', 'string', 'max:180', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique('articles', 'slug->ar')->ignore($this->route('article'))],
            'slug.en' => ['required_with:slug', 'string', 'max:180', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique('articles', 'slug->en')->ignore($this->route('article'))],
            'type' => ['sometimes', 'array:ar,en'],
            'type.ar' => ['required_with:type', 'string', 'max:80'],
            'type.en' => ['required_with:type', 'string', 'max:80'],
            'summary' => ['sometimes', 'array:ar,en'],
            'summary.ar' => ['required_with:summary', 'string', 'max:500'],
            'summary.en' => ['required_with:summary', 'string', 'max:500'],
            'body' => ['sometimes', 'array:ar,en'],
            'body.ar' => ['required_with:body', 'array', $validRichDocument],
            'body.en' => ['required_with:body', 'array', $validRichDocument],
            'lead' => ['required_with:sections,closing', 'array:ar,en'],
            'lead.ar' => ['required_with:lead', 'string'],
            'lead.en' => ['required_with:lead', 'string'],
            'sections' => ['required_with:lead,closing', 'array:ar,en'],
            'sections.ar' => ['required_with:sections', 'array', 'min:1'],
            'sections.en' => ['required_with:sections', 'array', 'min:1'],
            'sections.*.*.heading' => ['required', 'string', 'max:180'],
            'sections.*.*.paragraphs' => ['required', 'array', 'min:1'],
            'sections.*.*.paragraphs.*' => ['required', 'string'],
            'sections.*.*.points' => ['nullable', 'array'],
            'sections.*.*.points.*' => ['nullable', 'string', 'max:500'],
            'sections.*.*.note' => ['nullable', 'string'],
            'closing' => ['required_with:lead,sections', 'array:ar,en'],
            'closing.ar' => ['required_with:closing', 'string'],
            'closing.en' => ['required_with:closing', 'string'],
            'image_alt' => ['sometimes', 'array:ar,en'],
            'image_alt.ar' => ['required_with:image_alt', 'string', 'max:250'],
            'image_alt.en' => ['required_with:image_alt', 'string', 'max:250'],
            'image_caption' => ['sometimes', 'array:ar,en'],
            'image_caption.ar' => ['nullable', 'string', 'max:500'],
            'image_caption.en' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['sometimes', 'array:ar,en'],
            'seo_title.ar' => ['required_with:seo_title', 'string', 'max:60'],
            'seo_title.en' => ['required_with:seo_title', 'string', 'max:60'],
            'seo_description' => ['sometimes', 'array:ar,en'],
            'seo_description.ar' => ['required_with:seo_description', 'string', 'max:155'],
            'seo_description.en' => ['required_with:seo_description', 'string', 'max:155'],
            'topic_keys' => ['sometimes', 'array', 'min:1', 'max:30'],
            'topic_keys.*' => ['required', 'string', 'max:80', 'distinct'],
            'service_keys' => ['sometimes', 'array', 'max:30'],
            'service_keys.*' => ['required', 'string', 'max:80', 'distinct'],
            'project_keys' => ['sometimes', 'array', 'max:30'],
            'project_keys.*' => ['required', 'string', 'max:80', 'distinct'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
