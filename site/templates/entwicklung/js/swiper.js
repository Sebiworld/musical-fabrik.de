import {ready} from './classes/hilfsfunktionen.js';
// import Swiper from 'swiper';

(async () => {
	const elemente = document.querySelectorAll('.swiper-container');
	if(elemente.length > 0){

		// const swiperLoad = await import('swiper');
		const swiperLoad = await import(/* webpackChunkName: "swiper-slider" */ 'swiper/dist/js/swiper.js');
		const Swiper = swiperLoad.default;

		ready(function(){
			for(let index in elemente){
				const element = elemente[index];
				if(typeof element !== 'object' || !(element instanceof Element)){
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