<x-filament-panels::page>
    <div class="space-y-8">
        @if ($issuedClientSecret !== null)
            <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
                <x-slot name="heading">Copy this secret now</x-slot>
                <x-slot name="description">It is shown once and is never stored in the admin interface, logs, or source control.</x-slot>

                <div class="grid gap-4 md:grid-cols-2" dir="ltr">
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Client ID</div>
                        <code class="mt-1 block break-all rounded-lg bg-gray-100 p-3 text-sm dark:bg-gray-800">{{ $issuedClientId }}</code>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Client secret</div>
                        <code class="mt-1 block break-all rounded-lg bg-gray-100 p-3 text-sm dark:bg-gray-800">{{ $issuedClientSecret }}</code>
                    </div>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section icon="heroicon-o-plus-circle">
            <x-slot name="heading">Create OAuth client</x-slot>
            <x-slot name="description">Machine clients use client credentials. Browser and mobile clients use Authorization Code + PKCE with refresh tokens.</x-slot>

            <form wire:submit="createClient" class="grid gap-5 md:grid-cols-2">
                <x-filament::input.wrapper label="Client name" :valid="! $errors->has('data.name')">
                    <x-filament::input wire:model="data.name" placeholder="Codex production" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper label="Client type" :valid="! $errors->has('data.type')">
                    <select wire:model.live="data.type" class="w-full border-0 bg-transparent text-sm focus:ring-0">
                        <option value="machine">Machine (client credentials)</option>
                        <option value="browser">Browser/mobile (PKCE)</option>
                    </select>
                </x-filament::input.wrapper>
                <x-filament::input.wrapper label="Exact redirect URIs" :valid="! $errors->has('data.redirect_uris')" class="md:col-span-2">
                    <textarea wire:model="data.redirect_uris" rows="3" placeholder="https://app.example.com/oauth/callback&#10;One HTTPS URL per line. Required for PKCE clients." class="w-full border-0 bg-transparent text-sm focus:ring-0"></textarea>
                </x-filament::input.wrapper>
                <div class="md:col-span-2">
                    <div class="mb-2 text-sm font-medium text-gray-950 dark:text-white">Allowed scopes</div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach (\App\Filament\Pages\ManageOAuthClients::AVAILABLE_SCOPES as $scope => $label)
                            <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                                <input type="checkbox" wire:model="data.scopes" value="{{ $scope }}" class="mt-0.5 rounded border-gray-300 text-primary-600" />
                                <span><span class="block font-medium">{{ $scope }}</span><span class="text-gray-500 dark:text-gray-400">{{ $label }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('data.scopes') <p class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <x-filament::button type="submit">Create client</x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-key">
            <x-slot name="heading">OAuth clients</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-start text-sm">
                    <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400"><tr><th class="py-3 pe-4">Client</th><th class="py-3 pe-4">Grant</th><th class="py-3 pe-4">Scopes</th><th class="py-3 pe-4">Status</th><th class="py-3 text-end">Controls</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->clients() as $client)
                            <tr>
                                <td class="py-4 pe-4"><div class="font-medium">{{ $client->name }}</div><code class="text-xs text-gray-500" dir="ltr">{{ $client->getKey() }}</code></td>
                                <td class="py-4 pe-4">{{ implode(', ', $client->grant_types) }}</td>
                                <td class="py-4 pe-4">{{ implode(', ', $client->scopes ?? []) }}</td>
                                <td class="py-4 pe-4">{{ $client->revoked ? 'Revoked' : 'Active' }}</td>
                                <td class="py-4 text-end">
                                    @if (! $client->revoked)
                                        <x-filament::button size="sm" color="gray" wire:click="editClient('{{ $client->getKey() }}')">Edit</x-filament::button>
                                        <x-filament::button size="sm" color="warning" wire:click="revokeTokens('{{ $client->getKey() }}')" wire:confirm="Revoke all current access and refresh tokens while keeping the client active?">Revoke tokens</x-filament::button>
                                    @endif
                                    @if (! $client->revoked && in_array('client_credentials', $client->grant_types, true))
                                        <x-filament::button size="sm" color="gray" wire:click="rotateSecret('{{ $client->getKey() }}')" wire:confirm="Rotate this secret? Existing client credentials will stop working.">Rotate secret</x-filament::button>
                                    @endif
                                    @if (! $client->revoked)
                                        <x-filament::button size="sm" color="danger" wire:click="revokeClient('{{ $client->getKey() }}')" wire:confirm="Revoke this client and all of its tokens?">Revoke</x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @if ($editingClientId !== null)
            <x-filament::section icon="heroicon-o-pencil-square">
                <x-slot name="heading">Edit OAuth client</x-slot>
                <form wire:submit="updateClient" class="grid gap-5 md:grid-cols-2">
                    <x-filament::input.wrapper label="Client name" :valid="! $errors->has('editData.name')">
                        <x-filament::input wire:model="editData.name" />
                    </x-filament::input.wrapper>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Redirect URIs are locked to exact HTTPS locations. Leave this empty only for a machine client.</div>
                    <x-filament::input.wrapper label="Exact redirect URIs" :valid="! $errors->has('editData.redirect_uris')" class="md:col-span-2">
                        <textarea wire:model="editData.redirect_uris" rows="3" class="w-full border-0 bg-transparent text-sm focus:ring-0"></textarea>
                    </x-filament::input.wrapper>
                    <div class="md:col-span-2">
                        <div class="mb-2 text-sm font-medium text-gray-950 dark:text-white">Allowed scopes</div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach (\App\Filament\Pages\ManageOAuthClients::AVAILABLE_SCOPES as $scope => $label)
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                                    <input type="checkbox" wire:model="editData.scopes" value="{{ $scope }}" class="mt-0.5 rounded border-gray-300 text-primary-600" />
                                    <span><span class="block font-medium">{{ $scope }}</span><span class="text-gray-500 dark:text-gray-400">{{ $label }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-2 flex justify-end"><x-filament::button type="submit">Save client</x-filament::button></div>
                </form>
            </x-filament::section>
        @endif

        <x-filament::section icon="heroicon-o-shield-check">
            <x-slot name="heading">Recent editorial API audit events</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-start text-sm">
                    <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400"><tr><th class="py-3 pe-4">When</th><th class="py-3 pe-4">Action</th><th class="py-3 pe-4">Outcome</th><th class="py-3 pe-4">Article</th><th class="py-3">Request ID</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->audits() as $audit)
                            <tr><td class="py-3 pe-4">{{ $audit->occurred_at?->diffForHumans() }}</td><td class="py-3 pe-4">{{ $audit->action }}</td><td class="py-3 pe-4">{{ $audit->outcome }}</td><td class="py-3 pe-4">{{ $audit->article?->key ?? '—' }}</td><td class="py-3"><code class="text-xs">{{ $audit->request_id }}</code></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
