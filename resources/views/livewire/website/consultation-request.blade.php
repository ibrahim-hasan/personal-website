@php
    $consultationFields = [
        'name' => 'consultation-name',
        'email' => 'consultation-email',
        'company' => 'consultation-company',
        'role' => 'consultation-role',
        'service' => 'consultation-service',
        'challenge' => 'consultation-challenge',
        'timing' => 'consultation-timing',
    ];
    $fieldErrors = [];

    foreach ($consultationFields as $field => $id) {
        $message = $errors->first("form.{$field}") ?: $errors->first($field);

        if ($message !== '') {
            $fieldErrors[$field] = $message;
        }
    }

    $hasAnalyticsErrorState = $errors->any() || $errorMessage !== '' || $analyticsErrorCategory !== '';
    $analyticsErrorStateCategory = $errors->has('cf-turnstile-response')
        ? 'turnstile'
        : $analyticsErrorCategory;
    if ($analyticsErrorStateCategory === '' && $errors->any()) {
        $analyticsErrorStateCategory = 'validation';
    }
    if ($errorMessage !== '' && in_array($analyticsErrorCategory, ['validation', 'turnstile', 'rate_limited', 'unknown'], true)) {
        $analyticsErrorStateCategory = $analyticsErrorCategory;
    }
    $analyticsErrorStateCategory = in_array($analyticsErrorStateCategory, ['validation', 'turnstile', 'rate_limited', 'unknown'], true)
        ? $analyticsErrorStateCategory
        : 'unknown';
@endphp

<div class="consultation-form-shell">
    @if ($submitted)
        <div class="consultation-success" role="status" aria-live="polite" @if ($analyticsSuccess) data-analytics-consultation-success @endif>
            <span class="consultation-success__mark" aria-hidden="true">
                <x-phosphor-check class="h-8 w-8" />
            </span>
            <p>{{ __('site.consultation.kicker') }}</p>
            <h3 id="consultation-success" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ __('site.consultation.success_title') }}</h3>
            <span>{{ __('site.consultation.success_body') }}</span>
            @if ($publicReference !== '')
                <p><strong>{{ __('site.consultation.public_reference', ['reference' => $publicReference]) }}</strong></p>
            @endif
        </div>
    @else
        <form method="POST" action="{{ localized_route('contact.store') }}" wire:submit="submit" class="consultation-form" novalidate data-analytics-consultation-form>
            @csrf
            <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
            @if ($hasAnalyticsErrorState)
                <span hidden data-analytics-consultation-error="{{ $analyticsErrorStateCategory }}"></span>
            @endif

            <div class="sr-only" aria-hidden="true" inert>
                <label for="consultation-website">Website</label>
                <input id="consultation-website" name="website" type="text" wire:model="form.website" tabindex="-1" autocomplete="off" aria-hidden="true">
            </div>

            @if ($fieldErrors !== [])
                <div id="consultation-error-summary" class="form-alert" role="alert" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">
                    <h3>{{ __('site.consultation.error_summary_title') }}</h3>
                    <ul>
                        @foreach ($fieldErrors as $field => $message)
                            <li><a href="#{{ $consultationFields[$field] }}">{{ $message }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->has('consultation'))
                <p class="form-alert" role="alert">{{ $errors->first('consultation') }}</p>
            @endif

            <div class="consultation-form__grid">
                <div class="form-field">
                    <label for="consultation-name">{{ __('site.consultation.name') }}</label>
                    <input
                        id="consultation-name"
                        name="name"
                        type="text"
                        wire:model.blur.live="form.name"
                        autocomplete="name"
                        @if (isset($fieldErrors['name'])) aria-describedby="consultation-name-error" aria-invalid="true" @endif
                    >
                    @if (isset($fieldErrors['name'])) <p id="consultation-name-error" class="form-error">{{ $fieldErrors['name'] }}</p> @endif
                </div>

                <div class="form-field">
                    <label for="consultation-email">{{ __('site.consultation.email') }}</label>
                    <input
                        id="consultation-email"
                        name="email"
                        type="email"
                        wire:model.blur.live="form.email"
                        autocomplete="email"
                        inputmode="email"
                        @if (isset($fieldErrors['email'])) aria-describedby="consultation-email-error" aria-invalid="true" @endif
                    >
                    @if (isset($fieldErrors['email'])) <p id="consultation-email-error" class="form-error">{{ $fieldErrors['email'] }}</p> @endif
                </div>

                <div class="form-field">
                    <label for="consultation-company">{{ __('site.consultation.company') }}</label>
                    <input
                        id="consultation-company"
                        name="company"
                        type="text"
                        wire:model.blur.live="form.company"
                        autocomplete="organization"
                        @if (isset($fieldErrors['company'])) aria-describedby="consultation-company-error" aria-invalid="true" @endif
                    >
                    @if (isset($fieldErrors['company'])) <p id="consultation-company-error" class="form-error">{{ $fieldErrors['company'] }}</p> @endif
                </div>

                <div class="form-field">
                    <label for="consultation-role">{{ __('site.consultation.role') }}</label>
                    <input
                        id="consultation-role"
                        name="role"
                        type="text"
                        wire:model.blur.live="form.role"
                        autocomplete="organization-title"
                        @if (isset($fieldErrors['role'])) aria-describedby="consultation-role-error" aria-invalid="true" @endif
                    >
                    @if (isset($fieldErrors['role'])) <p id="consultation-role-error" class="form-error">{{ $fieldErrors['role'] }}</p> @endif
                </div>
            </div>

            <fieldset id="consultation-service" class="service-choice" @if (isset($fieldErrors['service'])) aria-describedby="consultation-service-error" @endif>
                <legend>{{ __('site.consultation.service') }}</legend>
                <div class="service-choice__grid">
                    @foreach ($services as $service)
                        <label wire:key="consultation-service-{{ $service['key'] }}">
                            <input name="service" type="radio" value="{{ $service['key'] }}" wire:model.change.live="form.service">
                            <span>{{ $service['name'] }}</span>
                        </label>
                    @endforeach
                </div>
                @if (isset($fieldErrors['service'])) <p id="consultation-service-error" class="form-error">{{ $fieldErrors['service'] }}</p> @endif
            </fieldset>

            <div class="form-field form-field--textarea">
                <label for="consultation-challenge">{{ __('site.consultation.challenge') }}</label>
                <textarea
                    id="consultation-challenge"
                    name="challenge"
                    rows="6"
                    wire:model.blur.live="form.challenge"
                    aria-describedby="consultation-challenge-hint{{ isset($fieldErrors['challenge']) ? ' consultation-challenge-error' : '' }}"
                    @if (isset($fieldErrors['challenge'])) aria-invalid="true" @endif
                ></textarea>
                <p id="consultation-challenge-hint" class="form-hint">{{ __('site.consultation.challenge_hint') }}</p>
                @if (isset($fieldErrors['challenge'])) <p id="consultation-challenge-error" class="form-error">{{ $fieldErrors['challenge'] }}</p> @endif
            </div>

            <div class="form-field form-field--textarea">
                <label for="consultation-timing">{{ __('site.consultation.timing') }}</label>
                <textarea
                    id="consultation-timing"
                    name="timing"
                    rows="3"
                    wire:model.blur.live="form.timing"
                    @if (isset($fieldErrors['timing'])) aria-describedby="consultation-timing-error" aria-invalid="true" @endif
                ></textarea>
                @if (isset($fieldErrors['timing'])) <p id="consultation-timing-error" class="form-error">{{ $fieldErrors['timing'] }}</p> @endif
            </div>

            @if ($errorMessage)
                <p class="form-alert" role="alert">{{ $errorMessage }}</p>
            @endif

            <x-turnstile.widget explicit id="consultation-turnstile" class="consultation-form__turnstile" />

            <noscript>
                <p class="form-hint">
                    {{ __('site.consultation.javascript_required') }}
                    @foreach ($channels as $channel)
                        @if (is_string($channel['href'] ?? null))
                            <a href="{{ $channel['href'] }}" @if (($channel['value_direction'] ?? null) === 'ltr') dir="ltr" @endif>{{ $channel['label'] }}</a>@if (! $loop->last), @endif
                        @endif
                    @endforeach
                </p>
            </noscript>

            <div class="consultation-form__footer">
                <button
                    type="submit"
                    class="button-light"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                >
                    <span wire:loading.remove wire:target="submit">{{ __('site.consultation.submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('site.consultation.sending') }}</span>
                    <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" wire:loading.remove wire:target="submit" />
                </button>
                <p>
                    {!! __('site.consultation.privacy', [
                        'privacy' => '<a class="underline decoration-current/40 underline-offset-4 hover:decoration-current" data-no-navigate href="'.e(localized_route('privacy')).'" target="_blank" rel="noopener noreferrer">'.e(__('legal.documents.privacy')).'</a>',
                    ]) !!}
                </p>
            </div>
        </form>
    @endif
</div>
