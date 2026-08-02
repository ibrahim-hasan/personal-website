<?php

namespace Tests\Feature\Seo;

use Illuminate\Http\Request;
use Tests\TestCase;

class CanonicalUrlRedirectTest extends TestCase
{
    public function test_public_duplicate_paths_redirect_once_to_their_canonical_path_and_preserve_the_query(): void
    {
        $this->assertPermanentRedirect(
            '/services/?utm_source=canonical-test',
            url('/services').'?utm_source=canonical-test',
        );

        $this->assertPermanentRedirect('/en/about/', url('/en/about'));

        $this->assertPermanentRedirect('/index.php?ref=legacy', url('/').'?ref=legacy');

        $this->assertPermanentRedirect('/en/index.php', url('/en'));
    }

    public function test_canonical_paths_and_non_get_requests_are_not_redirected(): void
    {
        $this->get('/services')
            ->assertOk();

        $this->post('/contact/', [
            'name' => 'Canonical request',
            'email' => 'canonical@example.com',
            'message' => 'This request must not become a redirect.',
        ])->assertStatus(302);
    }

    private function assertPermanentRedirect(string $uri, string $target): void
    {
        $request = Request::create($uri, 'GET');
        $response = app()->handle($request);
        $path = (string) parse_url($target, PHP_URL_PATH);
        $query = parse_url($target, PHP_URL_QUERY);
        $expectedTarget = $request->getSchemeAndHttpHost().$path
            .(is_string($query) ? '?'.$query : '');

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame($expectedTarget, $response->headers->get('Location'));
    }
}
