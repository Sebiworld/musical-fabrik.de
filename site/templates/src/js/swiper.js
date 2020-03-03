/* jshint -W024 */
import { ready } from './classes/helpers.js';

function initElements() {
	const Swiper = this;
	const elements = document.querySelectorAll('.swiper-container');
	if (elements.length <= 0) {
		return false;
	}

	console.log("INIT ELEMENTS");

	for (let index in elements) {
		const element = elements[index];
		if (typeof element !== 'object' || !(element instanceof Element)) {
			continue;
		}

		if(typeof element.swiper === 'object' && element.swiper instanceof Swiper){
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
			lazy: {
				loadPrevNext: true,
				loadPrevNextAmount: 2
			}
		};

		if (element.hasAttribute('data-autoplay')) {
			let delay = 5000;
			if (!isNaN(parseInt(element.getAttribute('data-autoplay'))) && parseInt(element.getAttribute('data-autoplay')) > 1) {
				delay = parseInt(element.getAttribute('data-autoplay'));
			}
			swiperParams.autoplay = {
				delay: delay
			};
		}else if (element.hasAttribute('data-hover-autoplay')) {
			let delay = 2000;
			if (!isNaN(parseInt(element.getAttribute('data-hover-autoplay'))) && parseInt(element.getAttribute('data-hover-autoplay')) > 1) {
				delay = parseInt(element.getAttribute('data-hover-autoplay'));
			}
			swiperParams.autoplay = {
				delay: delay
			};
		}


		if (element.hasAttribute('data-loop')) {
			swiperParams.loop = true;
		}

		const swiper = new Swiper(element, swiperParams);

		if (element.hasAttribute('data-hover-autoplay')) {
			if (swiper.autoplay.running) {
				swiper.autoplay.stop();
			}
			element.addEventListener('mouseenter', () => {
				if (!swiper.autoplay.running) {
					swiper.autoplay.start();
				}
			});
			element.addEventListener('mouseleave', () => {
				if (swiper.autoplay.running) {
					swiper.autoplay.stop();
				}
			});
		}
	}
}

(async () => {
	const elements = document.querySelectorAll('.swiper-container');
	if (elements.length <= 0) {
		return;
	}

	const swiperLoad = await import(/* webpackChunkName: "swiper-slider" */ 'swiper/js/swiper.esm.js');
	const Swiper = swiperLoad.Swiper;

	// Install modules
	Swiper.use([swiperLoad.Navigation, swiperLoad.Virtual, swiperLoad.Keyboard, swiperLoad.Pagination, swiperLoad.Lazy, swiperLoad.HashNavigation, swiperLoad.History, swiperLoad.Autoplay, swiperLoad.EffectFade, swiperLoad.A11y]);

	ready(initElements.bind(Swiper));
	window.addEventListener('reinit', initElements.bind(Swiper));
})();