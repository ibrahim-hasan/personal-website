<x-layouts.athar :title="__('athar.access.title')">
    <section class="athar-panel" aria-labelledby="athar-access-title">
        <p class="athar-kicker">{{ __('athar.access.eyebrow') }}</p>
        <h1 id="athar-access-title">{{ __('athar.access.title') }}</h1>
        <p class="athar-lead">{{ __('athar.access.body') }}</p>
        <div class="athar-trace" aria-hidden="true"><span></span><i></i><span></span></div>
        @if (session('status'))<p class="athar-success" role="status">{{ session('status') }}</p>@endif
        @if ($codeSent)
            <div x-data="atharAccessCode({ expiresAt: @js($codeExpiresAt), resendAvailableAt: @js($resendAvailableAt), attemptsRemaining: @js($attemptsRemaining), messages: @js(__('athar.access.code_status')) })">
                <form method="post" action="{{ localized_route('athar.verify', ['token' => request()->route('token')]) }}" class="athar-form">
                    @csrf
                    <label id="code-label">{{ __('athar.access.code_label') }}</label>
                    <div class="athar-code-inputs" role="group" aria-labelledby="code-label" aria-describedby="code-help" dir="ltr">
                        @foreach (range(0, 5) as $index)
                            <input
                                class="athar-code-input"
                                type="text"
                                name="code_digits[]"
                                inputmode="numeric"
                                autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                                maxlength="1"
                                pattern="[0-9٠-٩۰-۹]"
                                required
                                aria-label="{{ __('athar.access.code_digit', ['number' => $index + 1]) }}"
                                data-athar-code-digit
                                @input="handleInput($event, {{ $index }})"
                                @keydown="handleKeydown($event, {{ $index }})"
                                @paste="handlePaste($event, {{ $index }})"
                                @focus="$event.target.select()"
                                @if ($errors->has('code')) aria-invalid="true" @endif
                                @if ($index === 0) autofocus @endif
                            >
                        @endforeach
                    </div>
                    <p id="code-help" class="athar-help" aria-live="polite" x-text="statusMessage()">{{ __('athar.access.code_help') }}</p>
                    @error('code')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                    <button class="athar-button" type="submit" :disabled="verifyIsDisabled()">{{ __('athar.access.verify') }}</button>
                </form>
                <form method="post" action="{{ localized_route('athar.code', ['token' => request()->route('token')]) }}" class="athar-form athar-form--secondary">
                    @csrf
                    <button class="athar-link-button" type="submit" :disabled="resendIsLocked()" :aria-label="resendLabel()" x-text="resendLabel()">{{ __('athar.access.resend') }}</button>
                </form>
            </div>
        @else
            <form method="post" action="{{ localized_route('athar.code', ['token' => request()->route('token')]) }}" class="athar-form">
                @csrf
                <label for="email">{{ __('athar.access.email_label') }}</label>
                <input id="email" name="email" type="email" autocomplete="email" required autofocus>
                <p class="athar-help">{{ __('athar.access.email_help') }}</p>
                @error('email')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                <x-turnstile.widget />
                @error('turnstile')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                <button class="athar-button" type="submit">{{ __('athar.access.send_code') }}</button>
            </form>
        @endif
        <p class="athar-privacy">
            <span>{{ __('legal.privacy.athar_short_prefix') }}</span>
            <a href="{{ localized_route('privacy') }}">{{ __('legal.documents.privacy') }}</a>
        </p>
    </section>
</x-layouts.athar>
