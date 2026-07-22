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
        'articles:read' => 'Read articles and drafts',
        'articles:write' => 'Create and update drafts',
        'articles:publish' => 'Publish and unpublish articles',
        'articles:archive' => 'Archive and restore articles',
        'media:write' => 'Upload and remove article media',
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
        return 'OAuth Clients';
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
            $this->addError('data.redirect_uris', 'At least one exact HTTPS redirect URI is required for a browser client.');

            return;
        }

        foreach ($redirectUris as $redirectUri) {
            if (! filter_var($redirectUri, FILTER_VALIDATE_URL) || ! str_starts_with($redirectUri, 'https://')) {
                $this->addError('data.redirect_uris', 'Every redirect URI must be an exact HTTPS URL.');

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

        Notification::make()->title('OAuth client created')->success()->send();
    }

    public function rotateSecret(string $clientId, ClientRepository $clients): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($clientId);

        if (! in_array('client_credentials', $client->grant_types, true)) {
            Notification::make()->title('Public PKCE clients do not have a secret to rotate.')->warning()->send();

            return;
        }

        $clients->regenerateSecret($client);
        $this->issuedClientId = (string) $client->getKey();
        $this->issuedClientSecret = $client->plainSecret;

        Notification::make()->title('Client secret rotated.')->success()->send();
    }

    public function revokeClient(string $clientId, ClientRepository $clients): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($clientId);
        $clients->delete($client);

        Notification::make()->title('Client and its tokens were revoked.')->success()->send();
    }

    public function revokeTokens(string $clientId): void
    {
        abort_unless(static::canAccess(), 403);
        $client = Client::query()->where('revoked', false)->findOrFail($clientId);
        $client->tokens()->with('refreshToken')->each(function ($token): void {
            $token->refreshToken?->revoke();
            $token->revoke();
        });

        Notification::make()->title('All active tokens for this client were revoked.')->success()->send();
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
            $this->addError('editData.redirect_uris', 'At least one exact HTTPS redirect URI is required for a PKCE client.');

            return;
        }

        foreach ($redirectUris as $redirectUri) {
            if (! filter_var($redirectUri, FILTER_VALIDATE_URL) || ! str_starts_with($redirectUri, 'https://')) {
                $this->addError('editData.redirect_uris', 'Every redirect URI must be an exact HTTPS URL.');

                return;
            }
        }

        $client->forceFill([
            'name' => $validated['name'],
            'redirect_uris' => $redirectUris,
            'scopes' => array_values($validated['scopes']),
        ])->save();
        $this->editingClientId = null;

        Notification::make()->title('OAuth client updated.')->success()->send();
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
