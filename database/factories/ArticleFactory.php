<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'slug' => ['ar' => 'مقال-'.$key, 'en' => 'article-'.$key],
            'title' => ['ar' => 'عنوان عربي', 'en' => fake()->sentence(5)],
            'summary' => ['ar' => 'ملخص عربي', 'en' => fake()->sentence(12)],
            'seo_title' => ['ar' => 'عنوان عربي', 'en' => fake()->sentence(5)],
            'seo_description' => ['ar' => 'وصف عربي', 'en' => fake()->sentence(12)],
            'type' => ['ar' => 'مقال', 'en' => 'Article'],
            'lead' => ['ar' => 'مقدمة عربية', 'en' => fake()->paragraph()],
            'sections' => [
                'ar' => [['heading' => 'عنوان القسم', 'paragraphs' => ['نص القسم']]],
                'en' => [['heading' => 'Section heading', 'paragraphs' => [fake()->paragraph()]]],
            ],
            'closing' => ['ar' => 'خلاصة عربية', 'en' => fake()->sentence()],
            'body' => [
                'ar' => [
                    'type' => 'doc',
                    'content' => [
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat('نص عربي واضح. ', 35)]]],
                        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'الفكرة الرئيسية']]],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat('تفصيل عملي مفيد. ', 35)]]],
                    ],
                ],
                'en' => [
                    'type' => 'doc',
                    'content' => [
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat('Clear opening context. ', 35)]]],
                        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'The main idea']]],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat('Useful practical detail. ', 35)]]],
                    ],
                ],
            ],
            'published_at' => today(),
            'modified_at' => today(),
            'image' => 'images/ibrahim/ibrahim-speaking-hero.webp',
            'image_alt' => ['ar' => 'صورة توضيحية للمقال', 'en' => 'Editorial illustration for the article'],
            'image_caption' => ['ar' => '', 'en' => ''],
            'read_minutes' => ['ar' => 5, 'en' => 4],
            'topic_keys' => ['leadership'],
            'featured' => false,
            'source_url' => null,
            'is_published' => true,
        ];
    }
}
