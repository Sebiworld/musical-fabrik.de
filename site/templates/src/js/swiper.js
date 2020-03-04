/* jshint -W024 */
import { ready } from './classes/helpers.js';

(async () => {
	const elements = document.querySelectorAll('.swiper-container');
	if (elements.length <= 0) {
		return;
	}

	const swiperLoad = await import(/* webpackChunkName: "mf-slider" */ './classes/MfSwiper');
	const MfSwiper = swiperLoad.MfSwiper;
	ready(MfSwiper.initElements);
})();