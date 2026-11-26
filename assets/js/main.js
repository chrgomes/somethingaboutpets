/* Something About Pets – theme scripts (trimmed to what this site uses). */
(function ($) {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Sticky header
  var header = document.querySelector('header.style-2');
  if (header) {
    var onScroll = function () { header.classList.toggle('sticky', window.scrollY > 0); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Mobile menu
  $('.sidebar-button').on('click', function () { $('.main-menu').addClass('show-menu'); });
  $('.menu-close-btn').on('click', function () { $('.main-menu').removeClass('show-menu'); });
  $('.main-menu .menu-list a').on('click', function () { $('.main-menu').removeClass('show-menu'); });

  // Nicer select boxes (skip selects that opt out)
  if ($.fn.niceSelect) {
    $('select').not('.no-nice, [multiple]').niceSelect();
  }

  if (typeof Swiper === 'undefined') { return; }
  var autoplay = function (delay) { return reduceMotion ? false : { delay: delay, disableOnInteraction: false, pauseOnMouseEnter: true }; };

  // Hero slider
  if (document.querySelector('.hero2-slider')) {
    new Swiper('.hero2-slider', {
      slidesPerView: 1,
      spaceBetween: 12,
      effect: 'fade',
      fadeEffect: { crossFade: true },
      loop: true,
      speed: 1500,
      autoplay: autoplay(4000),
      pagination: { el: '.swiper-pagination121', clickable: true }
    });
  }

  // Services slider
  if (document.querySelector('.h2-services-slider')) {
    new Swiper('.h2-services-slider', {
      slidesPerView: 5,
      centeredSlides: true,
      loop: true,
      speed: 1500,
      autoplay: autoplay(2600),
      breakpoints: {
        280: { slidesPerView: 1, centeredSlides: false },
        480: { slidesPerView: 2, centeredSlides: false },
        768: { slidesPerView: 3, centeredSlides: false },
        992: { slidesPerView: 3 },
        1200: { slidesPerView: 5 }
      }
    });
  }

  // Testimonials slider
  if (document.querySelector('.h2-testimonial-slider')) {
    new Swiper('.h2-testimonial-slider', {
      spaceBetween: 24,
      slidesPerView: 2,
      loop: true,
      speed: 1200,
      autoplay: autoplay(5000),
      navigation: { nextEl: '.next-btn-5', prevEl: '.prev-btn-5' },
      scrollbar: { el: '.swiper-scrollbar', draggable: true },
      breakpoints: {
        280: { slidesPerView: 1 },
        992: { slidesPerView: 2 }
      }
    });
  }
})(jQuery);
