@props([
    'icon',
    'iconWidth' => 53,
    'iconHeight' => 53,
    'value',
    'label',
    'id' => 'stat-card-'.uniqid(),
])

@php
    $countValue = (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
@endphp

<article
class="x-stat-card"
x-init="
const countUp = new CountUp('{{ $id }}', {{ $countValue }}, {
    autoAnimate: true,
    duration: 2,
    autoAnimateOnce: true
})
"
>
    <div class="x-stat-card-icon"><img src="{{ $icon }}" alt="" width="{{ $iconWidth }}" height="{{ $iconHeight }}" class="mb-4 me-auto opacity-80"></div>
    <p class="gradient-text-gold md:text-[48px] text-[28px] font-bold leading-tight lg:text-[54px]">
        <span id="{{ $id }}">{{ $value }}</span>+
    </p>
    <p class="md:mt-2 mt-[6px] md:rtl:text-xl md:ltr:text-xl rtl:text-xs ltr:text-[10px] text-light-gray-500">{{ $label }}</p>
</article>
