<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureArticleScope;
use App\Models\EditorialApiAuditLog;
use App\Services\EditorialApi\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Token;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use Tests\TestCase;

class PassportEditorialOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_confidential_client_can_issue_a_short_lived_scoped_client_credentials_token(): void
    {
        $this->configurePassportKeys();
        Client::factory()->asClientCredentials()->create([
            'id' => 'codex-editorial-client',
            'secret' => 'codex-editorial-secret',
            'scopes' => ['articles:read'],
        ]);

        $response = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'codex-editorial-client',
            'client_secret' => 'codex-editorial-secret',
            'scope' => 'articles:read articles:publish',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer');
        $this->assertLessThanOrEqual(900, (int) $response->json('expires_in'));
        $this->assertSame(['articles:read'], Token::query()->sole()->scopes);
    }

    public function test_a_client_credentials_token_can_access_a_scoped_editorial_api_route(): void
    {
        $this->configurePassportKeys();
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('Editorial API client');
        $client->forceFill(['scopes' => ['articles:read']])->save();

        $token = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => 'articles:read',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->getJson('/api/v1/articles')
            ->assertOk();
    }

    public function test_a_public_browser_client_uses_pkce_authorization_code_and_refresh_grants(): void
    {
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'Future browser client',
            ['https://app.example.com/oauth/callback'],
            false,
        );
        $client->forceFill(['scopes' => ['articles:read']])->save();

        $this->assertFalse($client->confidential());
        $this->assertSame(['https://app.example.com/oauth/callback'], $client->redirect_uris);
        $this->assertContains('authorization_code', $client->grant_types);
        $this->assertContains('refresh_token', $client->grant_types);
        $this->assertSame(['articles:read'], $client->fresh()->scopes);
    }

    public function test_an_oauth_server_token_validation_failure_is_rejected(): void
    {
        $server = Mockery::mock(ResourceServer::class);
        $server->shouldReceive('validateAuthenticatedRequest')
            ->once()
            ->andThrow(OAuthServerException::accessDenied('Token signature verification failed.'));
        app()->instance(ResourceServer::class, $server);
        $request = Request::create('/api/v1/articles', 'POST');
        $request->headers->set('Authorization', 'Bearer token-that-must-not-be-logged');
        $request->attributes->set('editorial_api_request_id', 'fbd3d1e5-a0be-45a9-bbe1-1ca2b9d2c7a8');

        $this->expectException(AuthenticationException::class);

        (new EnsureArticleScope(app(ClientRepository::class)))->handle(
            $request,
            fn () => response()->json(),
            'articles:write',
        );
    }

    public function test_a_client_credentials_token_is_audited_without_a_user_id(): void
    {
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('Audit client');
        $token = new Token([
            'oauth_client_id' => $client->getKey(),
            'oauth_user_id' => $client->getKey(),
        ]);
        $request = Request::create('/api/v1/articles', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        $request->attributes->set('editorial_api_client', $client);
        $request->attributes->set('editorial_api_token', $token);
        $request->attributes->set('editorial_api_request_id', '2117bc20-9323-4bb2-9fc6-1db8699f73e7');

        app(Audit::class)->record($request, 'article.created', 'success');

        $audit = EditorialApiAuditLog::query()->sole();

        $this->assertSame($client->getKey(), $audit->client_id);
        $this->assertNull($audit->user_id);
    }

    private function configurePassportKeys(): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($key, $privateKey);
        $details = openssl_pkey_get_details($key);

        config()->set('passport.private_key', $privateKey);
        config()->set('passport.public_key', $details['key']);
    }
}
