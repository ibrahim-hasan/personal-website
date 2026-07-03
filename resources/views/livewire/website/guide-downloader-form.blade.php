<div>
    @if ($submitted)
        <div class="flex justify-center"
             x-data="{ show: false }"
             x-init="setTimeout(() => show = true, 100)"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center gap-2 py-3">
                <svg class="size-5 text-green-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm4.71 7.71-5.5 5.5a1 1 0 0 1-1.42 0l-2.5-2.5a1 1 0 0 1 1.42-1.42l1.79 1.79 4.79-4.79a1 1 0 1 1 1.42 1.42Z"/>
                </svg>
                <span class="text-green-300 font-medium">{{ __('guide.download_link_sent') }}</span>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="md:space-y-3 space-y-1">
            <div class="flex gap-3 flex-row sm:gap-0 md:rounded-[12px] rounded-[4px] border border-[#FEFEFE] md:p-3 p-[3px] md:h-[78px] h-[40px] overflow-hidden">
                <input
                    type="email"
                    wire:model="form.email"
                    placeholder="{{ __('Enter your email') }}"
                    class="flex-1 p-0 text-start md:text-xl text-sm text-light-gray-50 placeholder-light-gray-50/50 outline-none transition-colors sm:rounded-e-none sm:rounded-s-lg w-full"
                    aria-label="{{ __('Email') }}"
                    required>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75"
                    wire:target="submit"
                    class="btn-primary md:rounded-[12px] rounded-[4px] p-2 md:text-xl text-sm min-w-fit font-normal sm:rounded-s-none sm:rounded-e-lg text-[#414042] md:w-[142px] w-[106px] disabled:opacity-70">
                    <span wire:loading.remove wire:target="submit">{{ __('Download Guide') }}</span>
                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-1">
                        <svg class="animate-spin size-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>{{ __('Downloading') }}</span>
                    </span>
                </button>
            </div>

            @error('form.email')
                <p class="text-[14px] text-red-200">{{ $message }}</p>
            @enderror

            @if ($errorMessage)
                <p class="text-[14px] text-yellow-200">{{ $errorMessage }}</p>
            @endif

            <p class="text-sm text-yellow-50">{{ __('The guide will be sent directly to your email.') }}</p>
        </form>
    @endif
</div>
