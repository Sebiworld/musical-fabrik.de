import lazysizes from 'lazysizes';

// import a plugin
import 'lazysizes/plugins/parent-fit/ls.parent-fit';
import 'lazysizes/plugins/blur-up/ls.blur-up';
lazysizes.cfg.blurupMode = 'auto';
// import 'lazysizes/plugins/bgset/ls.bgset';

// polyfills
import 'lazysizes/plugins/respimg/ls.respimg';

if (!('object-fit' in document.createElement('a').style)) {
	require('lazysizes/plugins/object-fit/ls.object-fit');
}

import * as basicLightbox from 'basiclightbox';
// const lighboximages = document.querySelectorAll('a[data-open-imagelightbox], button[data-open-imagelightbox]');
// for (const image of lighboximages) {
// 	image.addEventListener('click', openImageLightbox);
// }

export const ImageHandler = {
	init(){
		ImageHandler.initLightboxHandlers();
		lazysizes.init();
	},
	initLightboxHandlers(wrapper) {
		let wrapperTmp = document;
		if (typeof wrapper === 'object' && wrapper instanceof Element) {
			wrapperTmp = wrapper;
		}
		const lighboximages = wrapperTmp.querySelectorAll('a[data-open-imagelightbox], button[data-open-imagelightbox]');
		for (const image of lighboximages) {
			ImageHandler.initLightboxHandler(image);
		}
	},
	initLightboxHandler(element) {
		if (typeof element !== 'object' || !(element instanceof Element)) {
			return;
		}

		element.removeEventListener('click', ImageHandler.openImageLightbox);
		element.addEventListener('click', ImageHandler.openImageLightbox);
	},
	openImageLightbox(e) {
		if (typeof e !== 'object' || !(e instanceof Event)) {
			return;
		}
		e.preventDefault();

		let element = e.target || e.srcElement;
		if (!element.hasAttribute('data-open-imagelightbox')) {
			element = element.closest('a[data-open-imagelightbox], button[data-open-imagelightbox]');
		}

		const url = element.getAttribute('data-open-imagelightbox');
		if (typeof url !== 'string' || url.length < 1) {
			return;
		}

		basicLightbox.create(/*html*/`
			<img src="${url}">
		`).show();
	}
};