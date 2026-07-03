// Swiper
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, Controller, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/pagination';

Swiper.use([Pagination, Navigation]);

if (document.querySelector('.x-philosophy-swiper')) {
    new Swiper('.x-philosophy-swiper', {
        modules: [Navigation],
        slidesPerView: 1.15,
        spaceBetween: 16,
        navigation: {
            nextEl: '.x-philosophy-swiper-next',
            prevEl: '.x-philosophy-swiper-prev',
        },
        breakpoints: {
            480: {
                slidesPerView: 1.25,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 24,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 48,
            },
        },
    });
}

if (document.querySelector('.x-participant-video-swiper')) {
    new Swiper('.x-participant-video-swiper', {
        modules: [Navigation],
        slidesPerView: 2,
        spaceBetween: 16,
        navigation: {
            nextEl: '.x-participant-video-swiper-next',
            prevEl: '.x-participant-video-swiper-prev',
        },
        breakpoints: {
            480: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 24,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 48,
            },
        },
    });
}

if (document.querySelector('.x-participant-review-swiper')) {
    new Swiper('.x-participant-review-swiper', {
        modules: [Navigation],
        slidesPerView: 2,
        spaceBetween: 16,
        navigation: {
            nextEl: '.x-participant-review-swiper-next',
            prevEl: '.x-participant-review-swiper-prev',
        },
        breakpoints: {
            480: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 24,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 48,
            },
        },
    });
}

if (document.querySelector('.x-articles-swiper')) {
    new Swiper('.x-articles-swiper', {
        modules: [Navigation],
        slidesPerView: 1,
        spaceBetween: 16,
        watchSlidesProgress: true,
        navigation: {
            nextEl: '.x-articles-swiper-next',
            prevEl: '.x-articles-swiper-prev',
        },
        breakpoints: {
            480: {
                slidesPerView: 1,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 24,
            },
            1024: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
        },
    });
}

if (document.querySelector('.x-guides-content-swiper')) {
    const guidesImageSwiper = new Swiper('.x-guides-image-swiper', {
        modules: [Pagination, Controller, EffectFade],
        slidesPerView: 1,
        spaceBetween: 0,
        allowTouchMove: false,
        effect: 'fade',
        fadeEffect: {
          crossFade: true
        },
        pagination: {
            el: '.x-guides-swiper-pagination',
            clickable: true,
        },
    });

    const guidesContentSwiper = new Swiper('.x-guides-content-swiper', {
        modules: [Navigation, Controller, EffectFade],
        slidesPerView: 1,
        spaceBetween: 0,
        effect: 'fade',
        fadeEffect: {
          crossFade: true
        },
        navigation: {
            nextEl: '.x-guides-swiper-next',
            prevEl: '.x-guides-swiper-prev',
        },
    });

    guidesContentSwiper.controller.control = guidesImageSwiper;
    guidesImageSwiper.controller.control = guidesContentSwiper;
}

// MODAL - Handel Sliders - Example Slider
// if (document.getElementsByClassName('slider-banners').length) {
//     const banners_swiper = new Swiper('.slider-banners', {
//         direction: 'horizontal',
//         loop: false,
//         rewind: false,
//         waitForTransition: true,
//         centeredSlides: true,
//         centerInsufficientSlides: true,
//         centeredSlidesBounds: true,
//         autoplay: {
//           delay: 5000,
//           pauseOnMouseEnter: false,
//           disableOnInteraction: false,
//         },
//         watchSlidesProgress: true,
//         // Navigation arrows
//         navigation: {
//             nextEl: '.swiper-button-next',
//             prevEl: '.swiper-button-prev',
//         },
//         // If we need pagination
//         pagination: {
//             el: '.swiper-pagination',
//         },
//         slidesPerView: 1,
//     });
// }

