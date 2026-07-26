<x-layouts.athar :title="__('athar.published.title')">
    <section class="athar-panel athar-panel--center" aria-labelledby="athar-published-title">
        @php
            $payloadLocale = isset($version->public_payload[app()->getLocale()]) ? app()->getLocale() : array_key_first($version->public_payload);
            $payload = $version->public_payload[$payloadLocale] ?? [];
        @endphp
        @if (in_array($version->status->value, ['published', 'hidden'], true))
            <h1 id="athar-published-title">{{ $version->status->value === 'hidden' ? __('athar.published.hidden') : __('athar.published.title') }}</h1>
            <p class="athar-lead">{{ $version->status->value === 'hidden' ? __('athar.published.hidden_body') : __('athar.published.body') }}</p>
            <div class="athar-final-preview athar-final-preview--readonly" dir="{{ $payloadLocale }}" lang="{{ $payloadLocale }}">
                <p class="athar-final-preview__label">{{ __('athar.published.words') }}</p>
                <blockquote class="athar-final-preview__quote">{{ $payload['text'] ?? '' }}</blockquote>
                @if (filled($payload['display_name'] ?? $version->display_name))<p class="athar-final-preview__name">{{ $payload['display_name'] ?? $version->display_name }}</p>@endif
                @if (filled($payload['context'] ?? ''))<p class="athar-final-preview__context-label">{{ __('athar.published.context') }}</p><p class="athar-final-preview__context">{{ $payload['context'] }}</p>@endif
            </div>
            @if ($version->status->value === 'published')
                <details class="athar-confirm">
                    <summary class="athar-button athar-button--quiet">{{ __('athar.published.withdraw') }}</summary>
                    <p>{{ __('athar.published.withdraw_body', ['destination' => $destination]) }}</p>
                    <form method="post" action="{{ localized_route('athar.withdraw', ['token' => request()->route('token')]) }}" class="athar-form">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="athar-button" type="submit">{{ __('athar.published.withdraw_confirm') }}</button>
                    </form>
                </details>
            @endif
            @if ($contribution->status->value === 'deletion_requested')
                <div class="athar-scope">
                    <p>{{ __('athar.published.deletion_pending') }}</p>
                    <form method="post" action="{{ localized_route('athar.deletion.cancel', ['token' => request()->route('token')]) }}" class="athar-form">
                        @csrf
                        <button class="athar-link-button" type="submit">{{ __('athar.published.deletion_cancel') }}</button>
                    </form>
                </div>
            @else
                <details class="athar-confirm athar-confirm--danger athar-form--secondary">
                    <summary class="athar-link-button">{{ __('athar.published.deletion') }}</summary>
                    <p>{{ __('athar.published.deletion_body') }}</p>
                    <form method="post" action="{{ localized_route('athar.deletion', ['token' => request()->route('token')]) }}" class="athar-form">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="athar-button athar-button--danger" type="submit">{{ __('athar.published.deletion_confirm') }}</button>
                    </form>
                </details>
            @endif
        @else
            <h1 id="athar-published-title">{{ __('athar.published.withdrawn') }}</h1>
            <p class="athar-lead">{{ __('athar.published.deletion_body') }}</p>
            <details class="athar-confirm">
                <summary class="athar-button">{{ __('athar.published.restore') }}</summary>
                <p>{{ __('athar.published.restore_body') }}</p>
                <form method="post" action="{{ localized_route('athar.restore', ['token' => request()->route('token')]) }}" class="athar-form">
                    @csrf
                    <input type="hidden" name="confirm" value="1">
                    <button class="athar-button" type="submit">{{ __('athar.published.restore_confirm') }}</button>
                </form>
            </details>
            @if ($contribution->status->value === 'deletion_requested')
                <div class="athar-scope">
                    <p>{{ __('athar.published.deletion_pending') }}</p>
                    <form method="post" action="{{ localized_route('athar.deletion.cancel', ['token' => request()->route('token')]) }}" class="athar-form">
                        @csrf
                        <button class="athar-link-button" type="submit">{{ __('athar.published.deletion_cancel') }}</button>
                    </form>
                </div>
            @else
                <details class="athar-confirm athar-confirm--danger athar-form--secondary">
                    <summary class="athar-link-button">{{ __('athar.published.deletion') }}</summary>
                    <p>{{ __('athar.published.deletion_body') }}</p>
                    <form method="post" action="{{ localized_route('athar.deletion', ['token' => request()->route('token')]) }}" class="athar-form">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="athar-button athar-button--danger" type="submit">{{ __('athar.published.deletion_confirm') }}</button>
                    </form>
                </details>
            @endif
        @endif
        @if (session('status'))<p class="athar-success" role="status">{{ session('status') }}</p>@endif
    </section>
</x-layouts.athar>
