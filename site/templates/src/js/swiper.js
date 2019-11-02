/* jshint -W024 */
import { ready } from './classes/helpers.js';

(async () => {
	const elements = document.querySelectorAll('.swiper-container');
	if (elements.length > 0) {

		const swiperLoad = await import(/* webpackChunkName: "swiper-slider" */ 'swiper/js/swiper.esm.js');
		const Swiper = swiperLoad.Swiper;

		// Install modules
		Swiper.use([swiperLoad.Navigation, swiperLoad.Virtual, swiperLoad.Keyboard, swiperLoad.Pagination, swiperLoad.Lazy, swiperLoad.HashNavigation, swiperLoad.History, swiperLoad.Autoplay, swiperLoad.EffectFade, swiperLoad.A11y]);

		ready(function () {
			for (let index in elements) {
				const element = elements[index];
				if (typeof element !== 'object' || !(element instanceof Element)) {
					continue;
				}

				let swiperParams = {
					pagination: '.swiper-pagination',
					navigation: {
						nextEl: '.swiper-button-next',
						prevEl: '.swiper-button-prev',
					},
					// pagination: {
					// 	el: '.swiper-pagination',
					// 	type: 'progressbar',
					// 	clickable: true
					// },
					slidesPerView: 'auto',
					spaceBetween: 0,
					watchSlidesVisibility: true,
					grabCursor: true,
					preloadImages: false,
					lazyLoading: true,
					lazyLoadingInPrevNext: true
				};

				const slider = new Swiper(element, swiperParams);
			}
		});
	}
})();