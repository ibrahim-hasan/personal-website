<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;
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
