@php
    $state = $getState();
    if (empty($state) || !is_array($state)) {
        echo '<span class="text-gray-400">-</span>';
        return;
    }
    $visible = $state['visible'] ?? [];
    $hidden = $state['hidden'] ?? [];
    $moreLabel = $state['more_label'] ?? '';
@endphp

@if(empty($hidden))
    <div class="flex flex-wrap items-center gap-1 w-[200px]">
        @foreach($visible as $name)
            <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20">{{ $name }}</span>
        @endforeach
    </div>
@else
    <div x-data="{ expanded: false }" class="flex flex-wrap items-center gap-1 w-[200px]">
        @foreach($visible as $name)
            <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20">{{ $name }}</span>
        @endforeach
        <template x-if="!expanded">
            <button
                type="button"
                @click="expanded = true"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 cursor-pointer transition-colors"
            >{{ $moreLabel }}</button>
        </template>
        <template x-if="expanded">
            <div class="flex flex-wrap items-center gap-1">
                @foreach($hidden as $name)
                    <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20">{{ $name }}</span>
                @endforeach
                <button
                    type="button"
                    @click="expanded = false"
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 cursor-pointer transition-colors"
                >{{ __('Show Less') }}</button>
            </div>
        </template>
    </div>
@endif
