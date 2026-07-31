<x-layouts.athar :title="__('athar.access.title')">
    <section class="athar-panel" aria-labelledby="athar-access-title">
        <p class="athar-kicker">{{ __('athar.access.eyebrow') }}</p>
        <h1 id="athar-access-title">{{ __('athar.access.title') }}</h1>
        <p class="athar-lead">{{ __('athar.access.body') }}</p>
        <div class="athar-trace" aria-hidden="true"><span></span><i></i><span></span></div>
        @if (session('status'))<p class="athar-success" role="status">{{ session('status') }}</p>@endif
        <div
            x-data="atharAccessFlow({
                codeSent: @js($codeSent),
                expiresAt: @js($codeExpiresAt),
                resendAvailableAt: @js($resendAvailableAt),
                attemptsRemaining: @js($attemptsRemaining ?? 0),
                messages: @js([
                    'code_status' => __('athar.access.code_status'),
                    'send_code' => __('athar.access.send_code'),
                    'sending' => __('athar.access.sending'),
                    'resending' => __('athar.access.resending'),
                    'resend' => __('athar.access.resend'),
                    'code_help' => __('athar.access.code_help'),
                    'request_failed' => __('athar.access.request_failed'),
                ]),
            })"
        >
            <div
                class="athar-form-status athar-form-status--success"
                x-cloak
                x-show="notice.message !== ''"
                :class="notice.type === 'error' ? 'athar-form-status--error' : 'athar-form-status--success'"
                :role="notice.type === 'error' ? 'alert' : 'status'"
                aria-live="polite"
                x-text="notice.message"
            ></div>

            <form
                @if ($codeSent) x-cloak @endif
                x-show="!codeSent"
                method="post"
                action="{{ localized_route('athar.code', ['token' => request()->route('token')]) }}"
                class="athar-form"
                @submit.prevent="requestCode($event)"
            >
                @csrf
                <label for="email">{{ __('athar.access.email_label') }}</label>
                <input id="email" name="email" type="email" autocomplete="email" required autofocus>
                <p class="athar-help">{{ __('athar.access.email_help') }}</p>
                <p class="athar-error" x-cloak x-show="fieldError('email')" x-text="fieldError('email')" role="alert"></p>
                @error('email')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                <x-turnstile.widget />
                <p class="athar-error" x-cloak x-show="fieldError('turnstile')" x-text="fieldError('turnstile')" role="alert"></p>
                @error('turnstile')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                <button class="athar-button" type="submit" :disabled="requestPending">
                    <span x-show="!requestPending">{{ __('athar.access.send_code') }}</span>
                    <span x-cloak x-show="requestPending" x-text="messages.sending"></span>
                </button>
            </form>

            <div @if (! $codeSent) x-cloak @endif x-show="codeSent">
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
                    <p id="code-help" class="athar-help" aria-live="polite">
                        <span x-text="statusMessage()">{{ __('athar.access.code_status.attempts_remaining', ['count' => $attemptsRemaining ?? 0]) }} {{ __('athar.access.code_help') }}</span>
                    </p>
                    <p class="athar-error" x-cloak x-show="fieldError('code')" x-text="fieldError('code')" role="alert"></p>
                    @error('code')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                    <button class="athar-button" type="submit" :disabled="verifyIsDisabled() || requestPending">{{ __('athar.access.verify') }}</button>
                </form>
                <form method="post" action="{{ localized_route('athar.code', ['token' => request()->route('token')]) }}" class="athar-form athar-form--secondary" @submit.prevent="requestCode($event)">
                    @csrf
                    <button class="athar-link-button" type="submit" :disabled="resendIsLocked() || requestPending" :aria-label="resendLabel()" x-text="resendLabel()">{{ __('athar.access.resend') }}</button>
                </form>
            </div>
        </div>
        <p class="athar-privacy">
            <span>{{ __('legal.privacy.athar_short_prefix') }}</span>
            <a href="{{ localized_route('privacy') }}" target="_blank" rel="noopener noreferrer">{{ __('legal.documents.privacy') }}</a>
        </p>
    </section>
</x-layouts.athar>
