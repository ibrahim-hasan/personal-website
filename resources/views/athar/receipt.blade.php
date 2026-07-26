<x-layouts.athar :title="__('athar.receipt.title')">
    <section class="athar-panel athar-panel--center" aria-labelledby="athar-receipt-title">
        @php
            $versionStatus = $version?->status->value;
        @endphp
        <h1 id="athar-receipt-title">{{ __('athar.receipt.title') }}</h1>
        <p class="athar-lead">{{ in_array($versionStatus, ['draft', 'awaiting_approval'], true) ? __('athar.receipt.ready_body') : __('athar.receipt.body') }}</p>
        @if (session('status'))<p class="athar-success" role="status">{{ session('status') }}</p>@endif
        @if (isset($version) && in_array($versionStatus, ['draft', 'awaiting_approval'], true))
            @php
                $payloadLocale = array_key_first($version->public_payload);
                $payload = $version->public_payload[$payloadLocale] ?? [];
                $pendingInput = $pendingInput ?? [];
                $publicText = old('text', $pendingInput['text'] ?? ($payload['text'] ?? ''));
                $identityDisplay = old('identity_display', $pendingInput['identity_display'] ?? ($payload['identity_display'] ?? $version->identity_display->value));
                $displayName = old('display_name', $pendingInput['display_name'] ?? ($payload['display_name'] ?? $version->display_name));
            @endphp
            <div class="athar-final-preview-wrap" x-data="atharReflection({ max: {{ \App\Support\AtharTextLimits::PUBLIC_MAX }}, initial: @js($publicText), messages: @js(__('athar.approval.counter')), identityDisplay: @js($identityDisplay), displayName: @js($displayName) })" x-init="init()">
                <div class="athar-final-preview" dir="{{ $payloadLocale }}" lang="{{ $payloadLocale }}">
                    <p class="athar-final-preview__label">{{ __('athar.approval.words') }}</p>
                    <blockquote class="athar-final-preview__quote" x-text="text">{{ $publicText }}</blockquote>
                    <p class="athar-final-preview__name" x-show="attribution()" x-text="attribution()">{{ $displayName }}</p>
                    @if (filled($payload['context'] ?? ''))<p class="athar-final-preview__context-label">{{ __('athar.approval.context') }}</p><p class="athar-final-preview__context">{{ $payload['context'] }}</p>@endif
                </div>
                @if (in_array($versionStatus, ['draft', 'awaiting_approval'], true))
                    <div class="athar-approval-editor">
                        <label for="athar-approval-text">{{ __('athar.approval.edit') }}</label>
                        <p class="athar-help">{{ __('athar.approval.edit_hint') }}</p>
                        <textarea id="athar-approval-text" form="athar-receipt-approval-form" class="athar-preview__editor" name="text" rows="7" maxlength="{{ \App\Support\AtharTextLimits::PUBLIC_MAX }}" required x-ref="field" @input="update($event.target.value)" aria-describedby="athar-approval-meter" @if ($errors->has('text')) aria-invalid="true" @endif>{{ $publicText }}</textarea>
                        <div id="athar-approval-meter" class="athar-writing-meter" aria-live="polite">
                            <span class="athar-writing-meter__message" x-text="message">{{ __('athar.approval.counter.start') }}</span>
                            <span class="athar-writing-meter__count"><strong x-text="formattedCount()">0</strong> / <span x-text="formattedMax()">{{ \App\Support\AtharTextLimits::PUBLIC_MAX }}</span></span>
                        </div>
                        <div class="athar-writing-meter__track" aria-hidden="true"><span :style="`width: ${progress}%`"></span></div>
                        @error('text')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                        <label for="athar-identity-display">{{ __('athar.approval.identity') }}</label>
                        <select id="athar-identity-display" form="athar-receipt-approval-form" name="identity_display" x-model="identityDisplay" @if ($errors->has('identity_display')) aria-invalid="true" @endif>
                            <option value="full_name" @selected($identityDisplay === 'full_name')>{{ __('athar.approval.name_full') }}</option>
                            <option value="first_name" @selected($identityDisplay === 'first_name')>{{ __('athar.approval.name_first') }}</option>
                            <option value="anonymous" @selected($identityDisplay === 'anonymous')>{{ __('athar.approval.name_anonymous') }}</option>
                        </select>
                        @error('identity_display')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                        <div x-show="identityDisplay !== 'anonymous'">
                            <label for="athar-display-name">{{ __('athar.approval.name_label') }}</label>
                            <input id="athar-display-name" form="athar-receipt-approval-form" name="display_name" type="text" value="{{ $displayName }}" maxlength="255" autocomplete="name" x-model="displayName" aria-describedby="athar-display-name-help" @if ($errors->has('display_name')) aria-invalid="true" @endif>
                            <p id="athar-display-name-help" class="athar-help">{{ __('athar.approval.name_help') }}</p>
                            @error('display_name')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endif
            </div>
        @endif
        @if (isset($version) && in_array($versionStatus, ['draft', 'awaiting_approval'], true))
            <p class="athar-scope">{{ __('athar.approval.scope', ['destination' => $destination]) }}</p>
            <form id="athar-receipt-approval-form" method="post" action="{{ localized_route('athar.approve', ['token' => request()->route('token')]) }}" class="athar-form">
                @csrf
                <label class="athar-check"><input type="checkbox" name="consent" value="1" required @checked(old('consent')) @if ($errors->has('consent')) aria-invalid="true" @endif> <span>{{ __('athar.approval.consent', ['destination' => $destination]) }}</span></label>
                @error('consent')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                <div class="athar-actions">
                    <button class="athar-button athar-button--quiet" type="submit" formaction="{{ localized_route('athar.approval.draft', ['token' => request()->route('token')]) }}" formnovalidate>{{ __('athar.approval.save_draft') }}</button>
                    <button class="athar-button" type="submit">{{ __('athar.approval.publish') }}</button>
                </div>
            </form>
        @endif
    </section>
</x-layouts.athar>
