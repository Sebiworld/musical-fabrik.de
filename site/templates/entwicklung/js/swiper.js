import { ready } from './classes/hilfsfunktionen.js';

(async () => {
	const elemente = document.querySelectorAll('.swiper-container');
	if (elemente.length > 0) {

		const swiperLoad = await import(/* webpackChunkName: "swiper-slider" */ 'swiper/dist/js/swiper.esm.js');
		const Swiper = swiperLoad.Swiper;

		// Install modules
		Swiper.use([swiperLoad.Navigation, swiperLoad.Virtual, swiperLoad.Keyboard, swiperLoad.Pagination, swiperLoad.Lazy, swiperLoad.HashNavigation, swiperLoad.History, swiperLoad.Autoplay, swiperLoad.EffectFade, swiperLoad.A11y]);

		ready(function () {
			for (let index in elemente) {
				const element = elemente[index];
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