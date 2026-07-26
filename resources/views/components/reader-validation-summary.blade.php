@props([
    'id' => 'reader-error-summary',
    'fields' => [],
    'errorBag' => 'default',
])

@php
    $messages = $errors->getBag($errorBag);
    $fieldErrors = [];

    foreach ($fields as $field => $fieldId) {
        if ($messages->has($field)) {
            $fieldErrors[$field] = [
                'id' => $fieldId,
                'message' => $messages->first($field),
            ];
        }
    }
@endphp

@if ($fieldErrors !== [])
    <div id="{{ $id }}" class="form-alert" role="alert" aria-labelledby="{{ $id }}-title">
        <h2 id="{{ $id }}-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">
            {{ __('reader_auth.error_summary_title') }}
        </h2>
        <ul class="mt-3 list-disc space-y-1 ps-5">
            @foreach ($fieldErrors as $fieldError)
                <li><a class="underline decoration-current/40 underline-offset-4 hover:decoration-current" href="#{{ $fieldError['id'] }}">{{ $fieldError['message'] }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
