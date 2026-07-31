<x-layouts.athar :title="__('athar.reflection.title')">
    <section class="athar-panel" aria-labelledby="athar-reflection-title">
        <h1 id="athar-reflection-title">{{ __('athar.reflection.title') }}</h1>
        <p class="athar-lead">{{ __('athar.reflection.body') }}</p>
        @if (session('status'))<p class="athar-success" role="status">{{ session('status') }}</p>@endif
        @php
            $draft = old('freeform', ($pendingInput['freeform'] ?? null) ?: data_get($contribution->draft_payload, 'freeform'));
        @endphp
        <form method="post" action="{{ localized_route('athar.submit', ['token' => request()->route('token')]) }}" class="athar-form" x-data="atharReflection({ max: {{ \App\Support\AtharTextLimits::REFLECTION_MAX }}, initial: @js($draft), messages: @js(__('athar.reflection.counter')) })" x-init="init()">
            @csrf
            <label for="freeform">{{ __('athar.reflection.note_label') }}</label>
            <textarea id="freeform" name="freeform" rows="10" maxlength="{{ \App\Support\AtharTextLimits::REFLECTION_MAX }}" required autofocus dir="auto" x-ref="field" @input="update($event.target.value)" aria-describedby="athar-reflection-meter" placeholder="{{ __('athar.reflection.note_placeholder') }}" @if ($errors->has('freeform')) aria-invalid="true" @endif>{{ $draft }}</textarea>
            <div id="athar-reflection-meter" class="athar-writing-meter" aria-live="polite">
                <span class="athar-writing-meter__message" x-text="message">{{ __('athar.reflection.counter.start') }}</span>
                <span class="athar-writing-meter__count"><strong x-text="formattedCount()">0</strong> / <span x-text="formattedMax()">{{ \App\Support\AtharTextLimits::REFLECTION_MAX }}</span></span>
            </div>
            <div class="athar-writing-meter__track" aria-hidden="true"><span :style="`width: ${progress}%`"></span></div>
            @error('freeform')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
            <div class="athar-actions">
                <button class="athar-button athar-button--quiet" type="submit" formaction="{{ localized_route('athar.draft', ['token' => request()->route('token')]) }}" formnovalidate>{{ __('athar.reflection.save_draft') }}</button>
                <button class="athar-button" type="submit">{{ __('athar.reflection.review') }}</button>
            </div>
        </form>
        <p class="athar-privacy">
            <span>{{ __('legal.privacy.athar_short_prefix') }}</span>
            <a href="{{ localized_route('privacy') }}" target="_blank" rel="noopener noreferrer">{{ __('legal.documents.privacy') }}</a>
        </p>
    </section>
</x-layouts.athar>
