<?php

namespace Tests\Feature\Ai;

use App\Ai\Agents\SeoMetadataGenerator;
use App\Filament\Components\AiSeoAction;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AiSeoActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_article_form_exposes_the_ai_action_only_to_an_authorized_editor(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        config()->set('ai.providers.openai.key', 'server-managed-key');
        Setting::setValue('ai_seo_enabled', true, 'ai');
        $article = Article::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get('/admin/articles/'.$article->getKey().'/edit')
            ->assertOk()
            ->assertSee(__('Generate SEO with AI'));

        $reader = User::factory()->create();
        $this->actingAs($reader);

        $this->assertFalse(AiSeoAction::make()->record($article)->isAuthorized());
    }

    public function test_the_article_ai_action_enforces_its_per_user_rate_limit_before_prompting(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        config()->set('ai.providers.openai.key', 'server-managed-key');
        Setting::setValue('ai_seo_enabled', true, 'ai');
        $article = Article::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $rateLimitKey = "ai-seo-generation:{$admin->getKey()}";

        RateLimiter::hit($rateLimitKey, 3600);
        RateLimiter::hit($rateLimitKey, 3600);
        RateLimiter::hit($rateLimitKey, 3600);
        RateLimiter::hit($rateLimitKey, 3600);
        SeoMetadataGenerator::fake()->preventStrayPrompts();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();

        Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callFormComponentAction('article-content', 'generateSeo')
            ->assertNotified(__('AI SEO limit reached'));

        SeoMetadataGenerator::assertNeverPrompted();
    }

    public function test_the_article_ai_action_populates_both_locales_with_one_request(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        config()->set('ai.providers.openai.key', 'server-managed-key');
        Setting::setValue('ai_seo_enabled', true, 'ai');
        $article = Article::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        RateLimiter::clear("ai-seo-generation:{$admin->getKey()}");
        $requests = 0;

        SeoMetadataGenerator::fake(function () use (&$requests): array {
            $requests++;

            return [
                'ar' => [
                    'title' => 'عنوان محسّن للمقال',
                    'description' => 'وصف عربي واضح ودقيق للمقال المنشور.',
                ],
                'en' => [
                    'title' => 'An improved article title',
                    'description' => 'A clear and accurate English description for the published article.',
                ],
            ];
        })->preventStrayPrompts();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();

        Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callFormComponentAction('article-content', 'generateSeo')
            ->assertSchemaStateSet([
                'seo_title.ar' => 'عنوان محسّن للمقال',
                'seo_description.ar' => 'وصف عربي واضح ودقيق للمقال المنشور.',
                'seo_title.en' => 'An improved article title',
                'seo_description.en' => 'A clear and accurate English description for the published article.',
            ])
            ->assertNotified(__('SEO metadata generated for Arabic and English.'));

        $this->assertSame(1, $requests);
    }
}
