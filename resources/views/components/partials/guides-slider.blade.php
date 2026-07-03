@if ($guides->isNotEmpty())
    <section class="relative overflow-hidden md:pt-[91px] pt-[44px] md:pb-[63px] pb-[0] md:mx-0 mx-6 md:px-0 px-6" aria-labelledby="leadmagnet-heading">
        <img src="{{ asset('images/objects/guide-bg.svg') }}" alt="" aria-hidden="true"
            class="absolute inset-0 h-full w-full object-cover">

        <div class="relative mx-auto max-w-[1093px]">
            <div class="flex flex-col items-center lg:flex-row lg:items-start lg:gap-[70px] gap-10 ">

                {{-- Image column with pagination dots --}}
                <div class="flex shrink-0 flex-col items-center lg:gap-[28px] gap-4 lg:w-[310px] w-[180px] order-2">
                    <div class="swiper x-guides-image-swiper w-full overflow-hidden">
                        <div class="swiper-wrapper">
                            @foreach ($guides as $guide)
                                <div class="swiper-slide flex justify-center">
                                    <img src="{{ $guide->getCoverImageUrl() }}"
                                        alt="{{ $guide->localizedTitle() }}"
                                        width="310" height="448"
                                        loading="lazy"
                                        class="h-auto w-[180px] rounded-sm object-contain lg:h-[448px] lg:w-[310px]">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($guides->count() > 1)
                        <div class="x-guides-swiper-pagination flex items-center justify-center gap-2"></div>
                    @endif
                </div>

                {{-- Content column with form and navigation --}}
                <div class="flex w-full flex-1 flex-col lg:max-w-[713px] order-1 lg:pt-[47px] pt-4">
                    <div class="swiper x-guides-content-swiper w-full overflow-hidden">
                        <div class="swiper-wrapper">
                            @foreach ($guides as $guide)
                                <div class="swiper-slide">
                                    <div class="flex flex-col lg:gap-6 gap-8">
                                        <div class="space-y-4 text-center md:text-start">
                                            <h2 id="{{ $loop->first ? 'leadmagnet-heading' : 'leadmagnet-heading-'.$guide->id }}"
                                                class="line-clamp-2 mx-auto w-[273px] text-[22px] lg:h-[144px] h-auto font-bold leading-[1.33] mb-4 text-yellow-400 md:mx-0 md:w-full md:text-[32px] lg:text-[54px]">
                                                <span class="lg:absolute relative line-clamp-2 lg:h-[155px] h-auto">{{ $guide->localizedTitle() }}</span>
                                            </h2>
                                            <p class="line-clamp-2 pe-0 text-sm font-medium leading-[1.6] text-light-gray-50 md:pe-6 md:text-xl lg:text-[20px] lg:leading-[1.44]">
                                                {{ $guide->localizedDescription() }}
                                            </p>
                                        </div>

                                        <livewire:website.guide-downloader-form :guide-id="$guide->id" :key="'guide-form-'.$guide->id" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($guides->count() > 1)
                        <div class="x-guides-slider__nav lg:mt-[55px] mt-4 flex w-full items-center justify-between rtl:flex-row-reverse">
                            <button type="button"
                                class="x-guides-swiper-next group flex ltr:row-reverse items-center lg:gap-[14px] gap-2 text-light-gray-50 transition-colors hover:text-yellow-400"
                                aria-label="{{ __('Next Guide') }}">
                                <span class="lg:text-xl text-base font-medium leading-[1.44]">{{ __('Next Guide') }}</span>

                                <span class="flex lg:size-[40px] size-[32px] items-center justify-center">
                                    <svg class="lg:size-10 size-6 rotate-180 transition-colors group-hover:text-yellow-400" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M42.6572 22.5C42.6572 11.3813 33.6197 2.34375 22.501 2.34375C11.3822 2.34375 2.34473 11.3813 2.34473 22.5C2.34473 33.6188 11.3822 42.6563 22.501 42.6563C33.6197 42.6563 42.6572 33.6188 42.6572 22.5ZM5.15723 22.5C5.15723 12.9375 12.9385 5.15625 22.501 5.15625C32.0635 5.15625 39.8447 12.9375 39.8447 22.5C39.8447 32.0625 32.0635 39.8438 22.501 39.8438C12.9385 39.8438 5.15723 32.0625 5.15723 22.5Z" fill="currentColor" />
                                        <path d="M28.1433 22.4998C28.1433 22.1435 28.0121 21.7873 27.7308 21.506L21.1121 14.8873C20.5683 14.3436 19.6683 14.3436 19.1246 14.8873C18.5809 15.4311 18.5809 16.3311 19.1246 16.8748L24.7496 22.4998L19.1246 28.1248C18.5809 28.6685 18.5809 29.5685 19.1246 30.1123C19.6683 30.656 20.5683 30.656 21.1121 30.1123L27.7308 23.4935C28.0121 23.2123 28.1433 22.856 28.1433 22.4998Z" fill="currentColor" />
                                    </svg>
                                </span>
                            </button>

                            <button type="button"
                                class="x-guides-swiper-prev group flex ltr:row-reverse items-center lg:gap-[14px] gap-2 text-light-gray-50 transition-colors hover:text-yellow-400"
                                aria-label="{{ __('Previous Guide') }}">
                                <span class="flex lg:size-[40px] size-[32px] items-center justify-center">
                                    <svg class="lg:size-10 size-6 -rotate-180 transition-colors group-hover:text-yellow-400" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M2.34277 22.5C2.34277 11.3813 11.3803 2.34375 22.499 2.34375C33.6178 2.34375 42.6553 11.3813 42.6553 22.5C42.6553 33.6188 33.6178 42.6563 22.499 42.6563C11.3803 42.6563 2.34277 33.6188 2.34277 22.5ZM39.8428 22.5C39.8428 12.9375 32.0615 5.15625 22.499 5.15625C12.9365 5.15625 5.15527 12.9375 5.15527 22.5C5.15527 32.0625 12.9365 39.8438 22.499 39.8438C32.0615 39.8438 39.8428 32.0625 39.8428 22.5Z" fill="currentColor" />
                                        <path d="M16.8567 22.4998C16.8567 22.1435 16.9879 21.7873 17.2692 21.506L23.8879 14.8873C24.4317 14.3436 25.3317 14.3436 25.8754 14.8873C26.4191 15.4311 26.4191 16.3311 25.8754 16.8748L20.2504 22.4998L25.8754 28.1248C26.4191 28.6685 26.4191 29.5685 25.8754 30.1123C25.3317 30.656 24.4317 30.656 23.8879 30.1123L17.2692 23.4935C16.9879 23.2123 16.8567 22.856 16.8567 22.4998Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="lg:text-xl text-base font-medium leading-[1.44]">{{ __('Previous Guide') }}</span>

                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
