<x-filament-panels::page>
    <div class="space-y-8">
        @if ($issuedClientSecret !== null)
            <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
                <x-slot name="heading">{{ __('admin.oauth_clients.secret.heading') }}</x-slot>
                <x-slot name="description">{{ __('admin.oauth_clients.secret.description') }}</x-slot>

                <div class="grid gap-4 md:grid-cols-2" dir="ltr">
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.oauth_clients.fields.client_id') }}</div>
                        <code class="mt-1 block break-all rounded-lg bg-gray-100 p-3 text-sm dark:bg-gray-800">{{ $issuedClientId }}</code>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.oauth_clients.fields.client_secret') }}</div>
                        <code class="mt-1 block break-all rounded-lg bg-gray-100 p-3 text-sm dark:bg-gray-800">{{ $issuedClientSecret }}</code>
                    </div>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section icon="heroicon-o-plus-circle">
            <x-slot name="heading">{{ __('admin.oauth_clients.create.heading') }}</x-slot>
            <x-slot name="description">{{ __('admin.oauth_clients.create.description') }}</x-slot>

            <form wire:submit="createClient" class="grid gap-5 md:grid-cols-2">
                <x-filament::input.wrapper :label="__('admin.oauth_clients.fields.client_name')" :valid="! $errors->has('data.name')">
                    <x-filament::input wire:model="data.name" :placeholder="__('admin.oauth_clients.placeholders.client_name')" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper :label="__('admin.oauth_clients.fields.client_type')" :valid="! $errors->has('data.type')">
                    <select wire:model.live="data.type" class="w-full border-0 bg-transparent text-sm focus:ring-0">
                        <option value="machine">{{ __('admin.oauth_clients.types.machine') }}</option>
                        <option value="browser">{{ __('admin.oauth_clients.types.browser') }}</option>
                    </select>
                </x-filament::input.wrapper>
                <x-filament::input.wrapper :label="__('admin.oauth_clients.fields.redirect_uris')" :valid="! $errors->has('data.redirect_uris')" class="md:col-span-2">
                    <textarea wire:model="data.redirect_uris" rows="3" dir="ltr" :placeholder="__('admin.oauth_clients.placeholders.redirect_uris')" class="w-full border-0 bg-transparent text-sm focus:ring-0"></textarea>
                </x-filament::input.wrapper>
                <div class="md:col-span-2">
                    <div class="mb-2 text-sm font-medium text-gray-950 dark:text-white">{{ __('admin.oauth_clients.fields.scopes') }}</div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->scopeLabels() as $scope => $label)
                            <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm transition-colors hover:border-primary-300 dark:border-gray-700 dark:hover:border-primary-700">
                                <input type="checkbox" wire:model="data.scopes" value="{{ $scope }}" class="mt-0.5 rounded border-gray-300 text-primary-600" />
                                <span><span class="block font-medium" dir="ltr">{{ $scope }}</span><span class="text-gray-500 dark:text-gray-400">{{ $label }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('data.scopes') <p class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <x-filament::button type="submit">{{ __('admin.oauth_clients.actions.create') }}</x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-key">
            <x-slot name="heading">{{ __('admin.oauth_clients.list.heading') }}</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-start text-sm">
                    <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400"><tr><th class="py-3 pe-4">{{ __('admin.oauth_clients.table.client') }}</th><th class="py-3 pe-4">{{ __('admin.oauth_clients.table.grant') }}</th><th class="py-3 pe-4">{{ __('admin.oauth_clients.table.scopes') }}</th><th class="py-3 pe-4">{{ __('admin.oauth_clients.table.status') }}</th><th class="py-3 text-end">{{ __('admin.oauth_clients.table.controls') }}</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->clients() as $client)
                            <tr>
                                <td class="py-4 pe-4"><div class="font-medium">{{ $client->name }}</div><code class="text-xs text-gray-500" dir="ltr">{{ $client->getKey() }}</code></td>
                                <td class="py-4 pe-4" dir="ltr">{{ implode(', ', $client->grant_types) }}</td>
                                <td class="py-4 pe-4" dir="ltr">{{ implode(', ', $client->scopes ?? []) }}</td>
                                <td class="py-4 pe-4">{{ $client->revoked ? __('admin.oauth_clients.status.revoked') : __('admin.oauth_clients.status.active') }}</td>
                                <td class="py-4 text-end">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if (! $client->revoked)
                                            <x-filament::button size="sm" color="gray" wire:click="editClient('{{ $client->getKey() }}')">{{ __('admin.oauth_clients.actions.edit') }}</x-filament::button>
                                            <x-filament::button size="sm" color="warning" wire:click="revokeTokens('{{ $client->getKey() }}')" :wire:confirm="__('admin.oauth_clients.confirmations.revoke_tokens')">{{ __('admin.oauth_clients.actions.revoke_tokens') }}</x-filament::button>
                                        @endif
                                        @if (! $client->revoked && in_array('client_credentials', $client->grant_types, true))
                                            <x-filament::button size="sm" color="gray" wire:click="rotateSecret('{{ $client->getKey() }}')" :wire:confirm="__('admin.oauth_clients.confirmations.rotate_secret')">{{ __('admin.oauth_clients.actions.rotate_secret') }}</x-filament::button>
                                        @endif
                                        @if (! $client->revoked)
                                            <x-filament::button size="sm" color="danger" wire:click="revokeClient('{{ $client->getKey() }}')" :wire:confirm="__('admin.oauth_clients.confirmations.revoke_client')">{{ __('admin.oauth_clients.actions.revoke') }}</x-filament::button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @if ($editingClientId !== null)
            <x-filament::section icon="heroicon-o-pencil-square">
                <x-slot name="heading">{{ __('admin.oauth_clients.edit.heading') }}</x-slot>
                <form wire:submit="updateClient" class="grid gap-5 md:grid-cols-2">
                    <x-filament::input.wrapper :label="__('admin.oauth_clients.fields.client_name')" :valid="! $errors->has('editData.name')">
                        <x-filament::input wire:model="editData.name" />
                    </x-filament::input.wrapper>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.oauth_clients.edit.redirect_uri_hint') }}</div>
                    <x-filament::input.wrapper :label="__('admin.oauth_clients.fields.redirect_uris')" :valid="! $errors->has('editData.redirect_uris')" class="md:col-span-2">
                        <textarea wire:model="editData.redirect_uris" rows="3" dir="ltr" class="w-full border-0 bg-transparent text-sm focus:ring-0"></textarea>
                    </x-filament::input.wrapper>
                    <div class="md:col-span-2">
                        <div class="mb-2 text-sm font-medium text-gray-950 dark:text-white">{{ __('admin.oauth_clients.fields.scopes') }}</div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($this->scopeLabels() as $scope => $label)
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm transition-colors hover:border-primary-300 dark:border-gray-700 dark:hover:border-primary-700">
                                    <input type="checkbox" wire:model="editData.scopes" value="{{ $scope }}" class="mt-0.5 rounded border-gray-300 text-primary-600" />
                                    <span><span class="block font-medium" dir="ltr">{{ $scope }}</span><span class="text-gray-500 dark:text-gray-400">{{ $label }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-2 flex justify-end"><x-filament::button type="submit">{{ __('admin.oauth_clients.actions.save') }}</x-filament::button></div>
                </form>
            </x-filament::section>
        @endif

        <x-filament::section icon="heroicon-o-shield-check">
            <x-slot name="heading">{{ __('admin.oauth_clients.audit.heading') }}</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-start text-sm">
                    <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400"><tr><th class="py-3 pe-4">{{ __('admin.oauth_clients.audit.when') }}</th><th class="py-3 pe-4">{{ __('admin.oauth_clients.audit.action') }}</th><th class="py-3 pe-4">{{ __('admin.oauth_clients.audit.outcome') }}</th><th class="py-3 pe-4">{{ __('admin.oauth_clients.audit.article') }}</th><th class="py-3">{{ __('admin.oauth_clients.audit.request_id') }}</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->audits() as $audit)
                            <tr><td class="py-3 pe-4">{{ $audit->occurred_at?->diffForHumans() }}</td><td class="py-3 pe-4" dir="ltr">{{ $audit->action }}</td><td class="py-3 pe-4" dir="ltr">{{ $audit->outcome }}</td><td class="py-3 pe-4" dir="ltr">{{ $audit->article?->key ?? '—' }}</td><td class="py-3"><code class="text-xs" dir="ltr">{{ $audit->request_id }}</code></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
