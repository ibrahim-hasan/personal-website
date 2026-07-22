<x-layouts.athar :title="__('athar.reflection.title')">
    <section class="athar-panel" aria-labelledby="athar-reflection-title">
        <h1 id="athar-reflection-title">{{ __('athar.reflection.title') }}</h1>
        <p class="athar-lead">{{ __('athar.reflection.body') }}</p>
        @if (session('status'))<p class="athar-success" role="status">{{ session('status') }}</p>@endif
        <form method="post" action="{{ route('athar.submit', ['token' => request()->route('token')]) }}" class="athar-form" x-data="atharReflection({ max: {{ \App\Support\AtharTextLimits::REFLECTION_MAX }}, messages: @js(__('athar.reflection.counter')) })" x-init="init()">
            @csrf
            <label for="freeform">{{ __('athar.reflection.note_label') }}</label>
            <textarea id="freeform" name="freeform" rows="10" maxlength="{{ \App\Support\AtharTextLimits::REFLECTION_MAX }}" required autofocus x-ref="field" @input="update($event.target.value)" aria-describedby="athar-reflection-meter">{{ old('freeform', data_get($contribution->draft_payload, 'freeform')) }}</textarea>
            <div id="athar-reflection-meter" class="athar-writing-meter" aria-live="polite">
                <span class="athar-writing-meter__message" x-text="message">{{ __('athar.reflection.counter.start') }}</span>
                <span class="athar-writing-meter__count"><strong x-text="formattedCount()">0</strong> / <span x-text="formattedMax()">{{ \App\Support\AtharTextLimits::REFLECTION_MAX }}</span></span>
            </div>
            <div class="athar-writing-meter__track" aria-hidden="true"><span :style="`width: ${progress}%`"></span></div>
            @error('freeform')<p class="athar-error" role="alert">{{ $message }}</p>@enderror
            <div class="athar-actions">
                <button class="athar-button" type="submit">{{ __('athar.reflection.review') }}</button>
            </div>
        </form>
        <p class="athar-privacy">
            <span>{{ __('legal.privacy.athar_short_prefix') }}</span>
            <a href="{{ localized_route('privacy') }}">{{ __('legal.documents.privacy') }}</a>
        </p>
    </section>
</x-layouts.athar>
