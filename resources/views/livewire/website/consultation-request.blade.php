<div class="consultation-form-shell" aria-live="polite">
    @if ($submitted)
        <div class="consultation-success" role="status">
            <span class="consultation-success__mark" aria-hidden="true">
                <x-phosphor-check class="h-8 w-8" />
            </span>
            <p>{{ __('site.consultation.kicker') }}</p>
            <h3>{{ __('site.consultation.success_title') }}</h3>
            <span>{{ __('site.consultation.success_body') }}</span>
        </div>
    @else
        <form wire:submit="submit" class="consultation-form" novalidate>
            <div class="sr-only" aria-hidden="true" inert>
                <label for="consultation-website">Website</label>
                <input id="consultation-website" type="text" wire:model="form.website" tabindex="-1" autocomplete="off" aria-hidden="true">
            </div>

            <div class="consultation-form__grid">
                <div class="form-field">
                    <label for="consultation-name">{{ __('site.consultation.name') }}</label>
                    <input
                        id="consultation-name"
                        type="text"
                        wire:model.blur.live="form.name"
                        autocomplete="name"
                        aria-describedby="consultation-name-error"
                        @error('form.name') aria-invalid="true" @enderror
                    >
                    @error('form.name') <p id="consultation-name-error" class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="consultation-email">{{ __('site.consultation.email') }}</label>
                    <input
                        id="consultation-email"
                        type="email"
                        wire:model.blur.live="form.email"
                        autocomplete="email"
                        inputmode="email"
                        aria-describedby="consultation-email-error"
                        @error('form.email') aria-invalid="true" @enderror
                    >
                    @error('form.email') <p id="consultation-email-error" class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field consultation-form__wide">
                    <label for="consultation-company">{{ __('site.consultation.company') }}</label>
                    <input
                        id="consultation-company"
                        type="text"
                        wire:model.blur.live="form.company"
                        autocomplete="organization"
                        aria-describedby="consultation-company-error"
                        @error('form.company') aria-invalid="true" @enderror
                    >
                    @error('form.company') <p id="consultation-company-error" class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <fieldset class="service-choice">
                <legend>{{ __('site.consultation.service') }}</legend>
                <div class="service-choice__grid">
                    @foreach ($services as $service)
                        <label wire:key="consultation-service-{{ $service['key'] }}">
                            <input type="radio" value="{{ $service['key'] }}" wire:model.change.live="form.service">
                            <span>{{ $service['name'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('form.service') <p class="form-error">{{ $message }}</p> @enderror
            </fieldset>

            <div class="form-field form-field--textarea">
                <label for="consultation-challenge">{{ __('site.consultation.challenge') }}</label>
                <textarea
                    id="consultation-challenge"
                    rows="6"
                    wire:model.blur.live="form.challenge"
                    aria-describedby="consultation-challenge-hint consultation-challenge-error"
                    @error('form.challenge') aria-invalid="true" @enderror
                ></textarea>
                <p id="consultation-challenge-hint" class="form-hint">{{ __('site.consultation.challenge_hint') }}</p>
                @error('form.challenge') <p id="consultation-challenge-error" class="form-error">{{ $message }}</p> @enderror
            </div>

            @if ($errorMessage)
                <p class="form-alert" role="alert">{{ $errorMessage }}</p>
            @endif

            <x-turnstile.widget callback="consultationTurnstileCallback" />

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
                        'privacy' => '<a class="underline decoration-current/40 underline-offset-4 hover:decoration-current" data-no-navigate href="'.e(localized_route('privacy')).'">'.e(__('legal.documents.privacy')).'</a>',
                    ]) !!}
                </p>
            </div>
        </form>
    @endif
</div>

@push('scripts')
    <script>
        window.consultationTurnstileCallback = function (token) {
            window.Livewire.dispatch('turnstile-resolved', { token: token });
        };
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('turnstile-reset', function () {
                if (window.turnstile) {
                    window.turnstile.reset();
                }
            });
        });
    </script>
@endpush
