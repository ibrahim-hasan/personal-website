<x-layouts.athar :title="__('athar.access.title')">
    <section class="athar-panel" aria-labelledby="athar-access-title">
        <p class="athar-kicker">{{ __('athar.access.eyebrow') }}</p>
        <h1 id="athar-access-title">{{ __('athar.access.title') }}</h1>
        <p class="athar-lead">{{ __('athar.access.body') }}</p>
        <div class="athar-trace" aria-hidden="true"><span></span><i></i><span></span></div>
        @if ($codeSent)
            <form method="post" action="{{ route('athar.verify', ['token' => request()->route('token')]) }}" class="athar-form">
                @csrf
                <label for="code">{{ __('athar.access.code_label') }}</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus>
                <p class="athar-help">{{ __('athar.access.code_help') }}</p>
                @error('code')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
                <button class="athar-button" type="submit">{{ __('athar.access.verify') }}</button>
            </form>
            <form method="post" action="{{ route('athar.code', ['token' => request()->route('token')]) }}" class="athar-form athar-form--secondary">
                @csrf
                <button class="athar-link-button" type="submit">{{ __('athar.access.resend') }}</button>
            </form>
        @else
            <form method="post" action="{{ route('athar.code', ['token' => request()->route('token')]) }}" class="athar-form">
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
