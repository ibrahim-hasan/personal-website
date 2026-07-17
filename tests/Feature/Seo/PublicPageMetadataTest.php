<?php

namespace Tests\Feature\Seo;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_metadata_uses_clean_localized_urls_regional_locales_and_full_preview_defaults(): void
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $arabicResponse = $this->get('/?utm_source=metadata-test');

        $arabicResponse
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$siteUrl.'">', false)
            ->assertSee('<link rel="alternate" hreflang="ar" href="'.$siteUrl.'">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="'.$siteUrl.'/en">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.$siteUrl.'">', false)
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">', false)
            ->assertSee('<meta property="og:locale" content="ar_AE">', false)
            ->assertSee('<meta property="og:locale:alternate" content="en_US">', false)
            ->assertSee('<meta property="og:image:type" content="image/png">', false)
            ->assertSee('<meta property="og:image:width" content="1586">', false)
            ->assertSee('<meta property="og:image:height" content="992">', false)
            ->assertSee('<meta property="og:image:alt" content="', false)
            ->assertSee('<meta name="twitter:image:alt" content="', false)
            ->assertDontSee('utm_source=metadata-test', false);

        $schema = $this->structuredData($arabicResponse->getContent());
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertContains('WebSite', array_column($schema['@graph'], '@type'));
        $this->assertContains('Person', array_column($schema['@graph'], '@type'));
        $this->assertContains('WebPage', array_column($schema['@graph'], '@type'));

        $this->get('/en?ref=ignored')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$siteUrl.'/en">', false)
            ->assertSee('<link rel="alternate" hreflang="ar" href="'.$siteUrl.'">', false)
            ->assertSee('<meta property="og:locale" content="en_US">', false)
            ->assertSee('<meta property="og:locale:alternate" content="ar_AE">', false)
            ->assertDontSee('ref=ignored', false);
    }

    public function test_about_page_identifies_the_profile_as_its_main_entity(): void
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $response = $this->get('/about')->assertOk();
        $schema = $this->structuredData($response->getContent());
        $profilePage = collect($schema['@graph'])->firstWhere('@type', 'ProfilePage');
        $person = collect($schema['@graph'])->firstWhere('@type', 'Person');

        $this->assertIsArray($profilePage);
        $this->assertIsArray($person);
        $this->assertSame($person['@id'], $profilePage['mainEntity']['@id']);
        $this->assertSame($siteUrl.'/about', $person['url']);
    }

    public function test_core_pages_expose_a_consistent_site_name_and_localized_breadcrumb_hierarchy(): void
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $arabicResponse = $this->get('/work')->assertOk();
        $arabicSchema = $this->structuredData($arabicResponse->getContent());
        $arabicWebsite = collect($arabicSchema['@graph'])->firstWhere('@type', 'WebSite');
        $arabicPage = collect($arabicSchema['@graph'])->firstWhere('@type', 'WebPage');
        $arabicBreadcrumb = collect($arabicSchema['@graph'])->firstWhere('@type', 'BreadcrumbList');

        $this->assertIsArray($arabicWebsite);
        $this->assertSame('إبراهيم حسن', $arabicWebsite['name']);
        $this->assertContains('Ibrahim Hasan', $arabicWebsite['alternateName']);
        $this->assertIsArray($arabicBreadcrumb);
        $this->assertSame($arabicBreadcrumb['@id'], $arabicPage['breadcrumb']['@id']);
        $this->assertSame([
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'الرئيسية',
                'item' => $siteUrl,
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'الأعمال',
                'item' => $siteUrl.'/work',
            ],
        ], $arabicBreadcrumb['itemListElement']);

        $englishResponse = $this->get('/en/about')->assertOk();
        $englishSchema = $this->structuredData($englishResponse->getContent());
        $englishWebsite = collect($englishSchema['@graph'])->firstWhere('@type', 'WebSite');
        $englishBreadcrumb = collect($englishSchema['@graph'])->firstWhere('@type', 'BreadcrumbList');

        $this->assertSame('إبراهيم حسن', $englishWebsite['name']);
        $this->assertContains('Ibrahim Hasan', $englishWebsite['alternateName']);
        $this->assertSame('Home', $englishBreadcrumb['itemListElement'][0]['name']);
        $this->assertSame($siteUrl.'/en', $englishBreadcrumb['itemListElement'][0]['item']);
        $this->assertSame('About', $englishBreadcrumb['itemListElement'][1]['name']);
        $this->assertSame($siteUrl.'/en/about', $englishBreadcrumb['itemListElement'][1]['item']);
    }

    public function test_home_page_has_no_redundant_breadcrumb_and_footer_uses_a_descriptive_contact_link(): void
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $response = $this->get('/')->assertOk();
        $schema = $this->structuredData($response->getContent());

        $this->assertNull(collect($schema['@graph'])->firstWhere('@type', 'BreadcrumbList'));
        $response->assertSee('href="'.$siteUrl.'/contact"', false);
        $response->assertSee('>تواصل</a>', false);
    }

    public function test_reader_authentication_and_private_library_pages_are_not_indexable(): void
    {
        $this->get('/reader/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow, noarchive">', false);

        $this->get('/en/reader/register')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow, noarchive">', false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/reader/library')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, noimageindex">', false);

        $this->actingAs($user)
            ->get('/en/reader/account')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, noimageindex">', false);
    }

    public function test_google_analytics_is_exposed_only_in_production_when_configured(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-L305M0T213');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('google-analytics-id', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false);

        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn (): string => 'production');

            $this->get('/')
                ->assertOk()
                ->assertSee('<meta name="google-analytics-id" content="G-L305M0T213">', false)
                ->assertDontSee('googletagmanager.com/gtag/js', false)
                ->assertDontSee('function gtag()', false);

            config()->set('services.google_analytics.measurement_id', '');

            $this->get('/')
                ->assertOk()
                ->assertDontSee('google-analytics-id', false);
        } finally {
            $this->app->detectEnvironment(fn (): string => $originalEnvironment);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredData(string $html): array
    {
        $matched = preg_match(
            '/<script type="application\/ld\+json">(.+?)<\/script>/s',
            $html,
            $matches,
        );

        $this->assertSame(1, $matched);

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
