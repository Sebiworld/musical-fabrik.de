/* jshint -W024 */
import { ready } from './classes/helpers.js';

(async () => {
	let lightGalleryCounter = 0;

	const sliderElements = document.querySelectorAll('.lightslider');
	const galleryElements = document.querySelectorAll('.lightgallery');

	if (sliderElements.length > 0 || galleryElements.length > 0) {
		const { default: lightGallery } = await import('lightgallery/lightgallery.es5');
		const { default: lgAutoplay } = await import('lightgallery/plugins/autoplay/lg-autoplay.es5');
		const { default: lgFullscreen } = await import('lightgallery/plugins/fullscreen/lg-fullscreen.es5');
		const { default: lgHash } = await import('lightgallery/plugins/hash/lg-hash.es5');
		// const { default: lgPager } = await import('lightgallery/plugins/pager/lg-pager.es5');
		// const { default: lgShare } = await import('lightgallery/plugins/share/lg-share.es5');
		const { default: lgThumbnail } = await import('lightgallery/plugins/thumbnail/lg-thumbnail.es5');
		const { default: lgVideo } = await import('lightgallery/plugins/video/lg-video.es5');
		const { default: lgZoom } = await import('lightgallery/plugins/zoom/lg-zoom.es5');

		if (sliderElements.length > 0) {
			ready(function () {
				for (let index in sliderElements) {
					const element = sliderElements[index];
					if (typeof element !== 'object' || !(element instanceof Element)) {
						continue;
					}

					const slider = lightGallery(element, {
						container: element,
						selector: '.lightslider-item',
						galleryId: ++lightGalleryCounter,
						hash: false,
						closable: false,
						loop: true,
						gallery: true,
						slideMargin: 0,
						thumbHeight: '60px',
						thumbWidth: 75,
						thumbMargin: 0,
						plugins: [lgZoom, lgThumbnail, lgHash, lgFullscreen, lgAutoplay, lgVideo],
						dynamic: false,
						exThumbImage: 'data-external-thumb-image',

						// Add maximize icon to enlarge the gallery
						showMaximizeIcon: true,
						licenseKey: VAR_LIGHTGALLERY_KEY
					});
					slider.openGallery();
				}
			});
		}

		if (galleryElements.length > 0) {
			ready(function () {
				for (let index in galleryElements) {
					const element = galleryElements[index];
					if (typeof element !== 'object' || !(element instanceof Element)) {
						continue;
					}

					lightGallery(element, {
						// container: element,
						selector: '.lightgallery-item',
						galleryId: ++lightGalleryCounter,
						plugins: [lgZoom, lgThumbnail, lgHash, lgFullscreen, lgAutoplay, lgVideo],
						exThumbImage: 'data-external-thumb-image',
						licenseKey: VAR_LIGHTGALLERY_KEY,
					});
				}
			});
		}
	}
})();