@php
    $variant = $variant ?? 'horizontal';
@endphp

<div @class([
    'admin-brand-lockup',
    'admin-brand-lockup--stacked' => $variant === 'stacked-on-dark',
])>
    <span class="sr-only">{{ __('admin.brand.owner') }}</span>

    @if ($variant === 'stacked-on-dark')
        <img
            class="admin-brand-lockup__wordmark admin-brand-lockup__wordmark--stacked"
            src="{{ asset('images/brand/ibrahim-wordmark-stacked-on-dark.svg') }}"
            width="223"
            height="259"
            alt=""
            aria-hidden="true"
        >
    @else
        <img
            class="admin-brand-lockup__wordmark admin-brand-lockup__wordmark--on-light"
            src="{{ asset('images/brand/ibrahim-wordmark-horizontal-on-light.svg') }}"
            width="474"
            height="152"
            alt=""
            aria-hidden="true"
        >
        <img
            class="admin-brand-lockup__wordmark admin-brand-lockup__wordmark--on-dark"
            src="{{ asset('images/brand/ibrahim-wordmark-horizontal-on-dark.svg') }}"
            width="474"
            height="152"
            alt=""
            aria-hidden="true"
        >
    @endif
</div>
