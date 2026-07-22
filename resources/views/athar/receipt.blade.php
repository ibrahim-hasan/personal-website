<x-layouts.athar :title="__('athar.receipt.title')">
    <section class="athar-panel athar-panel--center" aria-labelledby="athar-receipt-title">
        @php($versionStatus = $version?->status->value)
        <h1 id="athar-receipt-title">{{ __('athar.receipt.title') }}</h1>
        <p class="athar-lead">{{ in_array($versionStatus, ['draft', 'awaiting_approval'], true) ? __('athar.receipt.ready_body') : __('athar.receipt.body') }}</p>
        @if (session('status'))<p class="athar-success" role="status">{{ session('status') }}</p>@endif
        @if (isset($version) && in_array($versionStatus, ['draft', 'awaiting_approval'], true))
            @php($payloadLocale = array_key_first($version->public_payload))
            @php($payload = $version->public_payload[$payloadLocale] ?? [])
            <div class="athar-final-preview-wrap" x-data="atharReflection({ max: {{ \App\Support\AtharTextLimits::PUBLIC_MAX }}, initial: @js($payload['text'] ?? ''), messages: @js(__('athar.approval.counter')) })" x-init="init()">
                <div class="athar-final-preview" dir="{{ $payloadLocale }}" lang="{{ $payloadLocale }}">
                    <p class="athar-final-preview__label">{{ __('athar.approval.words') }}</p>
                    <blockquote class="athar-final-preview__quote" x-text="text">{{ $payload['text'] ?? '' }}</blockquote>
                    @if (filled($payload['context'] ?? ''))<p class="athar-final-preview__context-label">{{ __('athar.approval.context') }}</p><p class="athar-final-preview__context">{{ $payload['context'] }}</p>@endif
                </div>
                @if (in_array($versionStatus, ['draft', 'awaiting_approval'], true))
                    <div class="athar-approval-editor">
                        <label for="athar-approval-text">{{ __('athar.approval.edit') }}</label>
                        <p class="athar-help">{{ __('athar.approval.edit_hint') }}</p>
                        <textarea id="athar-approval-text" form="athar-receipt-approval-form" class="athar-preview__editor" name="text" rows="7" maxlength="{{ \App\Support\AtharTextLimits::PUBLIC_MAX }}" required x-ref="field" @input="update($event.target.value)" aria-describedby="athar-approval-meter">{{ $payload['text'] ?? '' }}</textarea>
                        <div id="athar-approval-meter" class="athar-writing-meter" aria-live="polite">
                            <span class="athar-writing-meter__message" x-text="message">{{ __('athar.approval.counter.start') }}</span>
                            <span class="athar-writing-meter__count"><strong x-text="formattedCount()">0</strong> / <span x-text="formattedMax()">{{ \App\Support\AtharTextLimits::PUBLIC_MAX }}</span></span>
                        </div>
                        <div class="athar-writing-meter__track" aria-hidden="true"><span :style="`width: ${progress}%`"></span></div>
                    </div>
                @endif
            </div>
        @endif
        @if (isset($version) && in_array($versionStatus, ['draft', 'awaiting_approval'], true))
            <p class="athar-scope">{{ __('athar.approval.scope', ['page' => $version->placement->label()]) }}</p>
            <form id="athar-receipt-approval-form" method="post" action="{{ route('athar.approve', ['token' => request()->route('token')]) }}" class="athar-form">
                @csrf
                <label class="athar-check"><input type="checkbox" name="consent" value="1" required> <span>{{ __('athar.approval.consent', ['page' => $version->placement->label()]) }}</span></label>
                <div class="athar-actions">
                    <button class="athar-button athar-button--quiet" type="submit" formaction="{{ route('athar.approval.draft', ['token' => request()->route('token')]) }}" formnovalidate>{{ __('athar.approval.save_draft') }}</button>
                    <button class="athar-button" type="submit">{{ __('athar.approval.publish') }}</button>
                </div>
            </form>
        @endif
    </section>
</x-layouts.athar>
