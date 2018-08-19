import {ready} from './classes/hilfsfunktionen.js';

(async () => {
	let lightGalleryCounter = 0;

	// Lightslider laden:
	const lightsliderElemente = document.querySelectorAll('.lightslider');
	if(lightsliderElemente.length > 0){
		await import(/* webpackChunkName: "lightslider" */ 'lightslider/src/js/lightslider.js');
		
		await import(/* webpackChunkName: "lightgallery" */ 'lightgallery/dist/js/lightgallery.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-autoplay/dist/lg-autoplay.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-fullscreen/dist/lg-fullscreen.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-hash/dist/lg-hash.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-pager/dist/lg-pager.js');
		// await import(/* webpackChunkName: "lightgallery" */ 'lg-share/dist/lg-share.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-thumbnail/dist/lg-thumbnail.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-video/dist/lg-video.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-zoom/dist/lg-zoom.js');

		ready(function(){
			for(let index in lightsliderElemente){
				const element = lightsliderElemente[index];
				if(typeof element !== 'object' || !(element instanceof Element)){
					continue;
				}

				$(element).lightSlider({
					autoWidth: true,
					loop: true,
					gallery: true,
					slideMargin: 0,

					onSliderLoad: function(el) {
						el.lightGallery({
							gallerId: ++lightGalleryCounter
						});
					}
				});
			}
		});
	}

	// Lightgallery laden [jQuery-Version]:
	const lightgalleryElemente = document.querySelectorAll('.lightgallery');
	if(lightgalleryElemente.length > 0){
		await import(/* webpackChunkName: "lightgallery" */ 'lightgallery/dist/js/lightgallery.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-autoplay/dist/lg-autoplay.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-fullscreen/dist/lg-fullscreen.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-hash/dist/lg-hash.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-pager/dist/lg-pager.js');
		// await import(/* webpackChunkName: "lightgallery" */ 'lg-share/dist/lg-share.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-thumbnail/dist/lg-thumbnail.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-video/dist/lg-video.js');
		await import(/* webpackChunkName: "lightgallery" */ 'lg-zoom/dist/lg-zoom.js');

		ready(function(){
			for(let index in lightgalleryElemente){
				const element = lightgalleryElemente[index];
				if(typeof element !== 'object' || !(element instanceof Element)){
					continue;
				}

				$(element).lightGallery({
					selector: '.lightgallery-item',
					galleryId: ++lightGalleryCounter
				});
			}
		});
	}

	// Lightgallery laden [Plain-JS]:
	// const lightgalleryElemente = document.querySelectorAll('.lightgallery');
	// if(lightgalleryElemente.length > 0){
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lightgallery.js/src/js/lightgallery.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-autoplay.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-fullscreen.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-hash.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-pager.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-share.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-thumbnail.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-video.js');
	// 	await import(/* webpackChunkName: "lightgallery" */ 'lg-zoom.js');

	// 	for(let index in lightgalleryElemente){
	// 		const element = lightgalleryElemente[index];
	// 		if(typeof element !== 'object' || !(element instanceof Element)){
	// 			continue;
	// 		}
	// 		lightGallery(element, {
	// 			selector: '.lightgallery-item',
	// 			galleryId: ++lightGalleryCounter
	// 		});
	// 		console.log("Galerie geladen.");
	// 	}
	// }

})();