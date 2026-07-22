<?php

namespace App\Filament\Pages;

use App\Models\EditorialApiAuditLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class ManageOAuthClients extends Page
{
    /** @var array<string, string> */
    public const array AVAILABLE_SCOPES = [
        'articles:read' => 'admin.oauth_clients.scopes.articles_read',
        'articles:write' => 'admin.oauth_clients.scopes.articles_write',
        'articles:publish' => 'admin.oauth_clients.scopes.articles_publish',
        'articles:archive' => 'admin.oauth_clients.scopes.articles_archive',
        'media:write' => 'admin.oauth_clients.scopes.media_write',
    ];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.manage-o-auth-clients';

    /** @var array{name: string, type: string, redirect_uris: string, scopes: list<string>} */
    public array $data = [
        'name' => '',
        'type' => 'machine',
        'redirect_uris' => '',
        'scopes' => ['articles:read', 'articles:write'],
    ];

    public ?string $issuedClientId = null;

    public ?string $issuedClientSecret = null;

    public ?string $editingClientId = null;

    /** @var array{name: string, redirect_uris: string, scopes: list<string>} */
    public array $editData = [
        'name' => '',
        'redirect_uris' => '',
        'scopes' => [],
    ];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.oauth_clients.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.oauth_clients.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public function createClient(ClientRepository $clients): void
    {
        abort_unless(static::canAccess(), 403);

        $validated = $this->validate([
            'data.name' => ['required', 'string', 'max:120'],
            'data.type' => ['required', 'in:machine,browser'],
            'data.redirect_uris' => ['nullable', 'string', 'max:4000'],
            'data.scopes' => ['required', 'array', 'min:1'],
            'data.scopes.*' => ['required', 'in:'.implode(',', array_keys(self::AVAILABLE_SCOPES))],
        ])['data'];
        $redirectUris = collect(preg_split('/\R+/', $validated['redirect_uris']) ?: [])
            ->map(fn (string $uri): string => trim($uri))
            ->filter()
            ->values()
            ->all();

        if ($validated['type'] === 'browser' && $redirectUris === []) {
            $this->addError('data.redirect_uris', __('admin.oauth_clients.validation.browser_redirect_uri_required'));

            return;
        }

        foreach ($redirectUris as $redirectUri) {
            if (! filter_var($redirectUri, FILTER_VALIDATE_URL) || ! str_starts_with($redirectUri, 'https://')) {
                $this->addError('data.redirect_uris', __('admin.oauth_clients.validation.redirect_uri_must_be_https'));

                return;
            }
        }

        $client = $validated['type'] === 'machine'
            ? $clients->createClientCredentialsGrantClient($validated['name'])
            : $clients->createAuthorizationCodeGrantClient($validated['name'], $redirectUris, false);
        $client->forceFill(['scopes' => array_values($validated['scopes'])])->save();

        $this->issuedClientId = (string) $client->getKey();
        $this->issuedClientSecret = $client->plainSecret;
        $this->data = [
            'name' => '',
            'type' => 'machine',
            'redirect_uris' => '',
            'scopes' => ['articles:read', 'articles:write'],
        ];

        Notification::make()->title(__('admin.oauth_clients.notifications.created'))->success()->send();
    }

    public function rotateSecret(string $clientId, ClientRepository $clients): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($clientId);

        if (! in_array('client_credentials', $client->grant_types, true)) {
            Notification::make()->title(__('admin.oauth_clients.notifications.pkce_client_has_no_secret'))->warning()->send();

            return;
        }

        $clients->regenerateSecret($client);
        $this->issuedClientId = (string) $client->getKey();
        $this->issuedClientSecret = $client->plainSecret;

        Notification::make()->title(__('admin.oauth_clients.notifications.secret_rotated'))->success()->send();
    }

    public function revokeClient(string $clientId, ClientRepository $clients): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($clientId);
        $clients->delete($client);

        Notification::make()->title(__('admin.oauth_clients.notifications.client_revoked'))->success()->send();
    }

    public function revokeTokens(string $clientId): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($clientId);
        $client->tokens()->with('refreshToken')->each(function ($token): void {
            $token->refreshToken?->revoke();
            $token->revoke();
        });

        Notification::make()->title(__('admin.oauth_clients.notifications.tokens_revoked'))->success()->send();
    }

    public function editClient(string $clientId): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($clientId);
        $this->editingClientId = (string) $client->getKey();
        $this->editData = [
            'name' => $client->name,
            'redirect_uris' => implode(PHP_EOL, $client->redirect_uris),
            'scopes' => $client->scopes ?? [],
        ];
    }

    public function updateClient(): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($this->editingClientId);
        $validated = $this->validate([
            'editData.name' => ['required', 'string', 'max:120'],
            'editData.redirect_uris' => ['nullable', 'string', 'max:4000'],
            'editData.scopes' => ['required', 'array', 'min:1'],
            'editData.scopes.*' => ['required', 'in:'.implode(',', array_keys(self::AVAILABLE_SCOPES))],
        ])['editData'];
        $redirectUris = collect(preg_split('/\R+/', $validated['redirect_uris']) ?: [])
            ->map(fn (string $uri): string => trim($uri))
            ->filter()
            ->values()
            ->all();

        if (in_array('authorization_code', $client->grant_types, true) && $redirectUris === []) {
            $this->addError('editData.redirect_uris', __('admin.oauth_clients.validation.pkce_redirect_uri_required'));

            return;
        }

        foreach ($redirectUris as $redirectUri) {
            if (! filter_var($redirectUri, FILTER_VALIDATE_URL) || ! str_starts_with($redirectUri, 'https://')) {
                $this->addError('editData.redirect_uris', __('admin.oauth_clients.validation.redirect_uri_must_be_https'));

                return;
            }
        }

        $client->forceFill([
            'name' => $validated['name'],
            'redirect_uris' => $redirectUris,
            'scopes' => array_values($validated['scopes']),
        ])->save();
        $this->editingClientId = null;

        Notification::make()->title(__('admin.oauth_clients.notifications.updated'))->success()->send();
    }

    /** @return array<string, string> */
    public static function scopeLabels(): array
    {
        return collect(self::AVAILABLE_SCOPES)
            ->map(fn (string $translation): string => __($translation))
            ->all();
    }

    /** @return Collection<int, Client> */
    public function clients(): Collection
    {
        return Client::query()->latest()->get();
    }

    /** @return Collection<int, EditorialApiAuditLog> */
    public function audits(): Collection
    {
        return EditorialApiAuditLog::query()
            ->with('article:id,key')
            ->latest('occurred_at')
            ->limit(20)
            ->get();
    }
}
