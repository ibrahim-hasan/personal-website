<x-layouts.athar :title="__('athar.receipt.title')">
    <section class="athar-panel athar-panel--center" aria-labelledby="athar-receipt-title">
        <h1 id="athar-receipt-title">{{ __('athar.receipt.title') }}</h1>
        <p class="athar-lead">{{ isset($version) && $version?->status->value === 'awaiting_approval' ? __('athar.receipt.ready_body') : __('athar.receipt.body') }}</p>
        @if (isset($version) && in_array($version?->status->value, ['draft', 'awaiting_approval'], true))
            @php($payloadLocale = array_key_first($version->public_payload))
            @php($payload = $version->public_payload[$payloadLocale] ?? [])
            <div class="athar-preview" dir="{{ $payloadLocale }}" lang="{{ $payloadLocale }}">
                <p class="athar-help">{{ $version->status->value === 'awaiting_approval' ? __('athar.approval.words') : __('athar.receipt.preview_label') }}</p>
                @if ($version->status->value === 'awaiting_approval')
                    <p class="athar-help">{{ __('athar.approval.edit_hint') }}</p>
                    <div class="athar-approval-editor" x-data="atharReflection({ max: {{ \App\Support\AtharTextLimits::PUBLIC_MAX }}, messages: @js(__('athar.approval.counter')) })" x-init="init()">
                        <textarea form="athar-receipt-approval-form" class="athar-preview__editor" name="text" rows="7" maxlength="{{ \App\Support\AtharTextLimits::PUBLIC_MAX }}" required x-ref="field" @input="update($event.target.value)" aria-describedby="athar-approval-meter">{{ $payload['text'] ?? '' }}</textarea>
                        <div id="athar-approval-meter" class="athar-writing-meter" aria-live="polite">
                            <span class="athar-writing-meter__message" x-text="message">{{ __('athar.approval.counter.start') }}</span>
                            <span class="athar-writing-meter__count"><strong x-text="formattedCount()">0</strong> / <span x-text="formattedMax()">{{ \App\Support\AtharTextLimits::PUBLIC_MAX }}</span></span>
                        </div>
                        <div class="athar-writing-meter__track" aria-hidden="true"><span :style="`width: ${progress}%`"></span></div>
                    </div>
                @else
                    <blockquote>{{ $payload['text'] ?? '' }}</blockquote>
                @endif
                @if (filled($payload['context'] ?? ''))<p class="athar-help">{{ __('athar.approval.context') }}</p><p>{{ $payload['context'] }}</p>@endif
            </div>
        @endif
        @if (isset($version) && $version?->status->value === 'awaiting_approval')
            <p class="athar-scope">{{ __('athar.approval.scope', ['page' => $version->placement->label()]) }}</p>
            <form id="athar-receipt-approval-form" method="post" action="{{ route('athar.approve', ['token' => request()->route('token')]) }}" class="athar-form">
                @csrf
                <label class="athar-check"><input type="checkbox" name="consent" value="1" required> <span>{{ __('athar.approval.consent', ['page' => $version->placement->label()]) }}</span></label>
                <div class="athar-actions"><button class="athar-button" type="submit">{{ __('athar.approval.publish') }}</button><a class="athar-link-button" href="{{ route('athar.show', ['token' => request()->route('token')]) }}">{{ __('athar.approval.private') }}</a></div>
            </form>
        @endif
    </section>
</x-layouts.athar>
