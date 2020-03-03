/* jshint -W024 */
import { ready } from "./classes/helpers.js";

(async () => {
	const elemente = document.querySelectorAll(".masonry-grid");
	if (elemente.length > 0) {

		const imagesloadedLoad = await import('imagesloaded');
		const ImagesLoaded = imagesloadedLoad.default;
		const msryLoad = await import(/* webpackChunkName: "masonry-import" */ 'masonry-layout');
		const Masonry = msryLoad.default;

		ready(function () {
			for (let index in elemente) {
				const element = elemente[index];
				if (typeof element !== "object" || !(element instanceof Element)) {
					continue;
				}

				let masonryGrid = new Masonry(element, {
					itemSelector: ".masonry-grid-item",
					columnWidth: ".masonry-grid-sizer",
					percentPosition: true
				});

				ImagesLoaded(element).on("progress", function () {
					masonryGrid.layout();
				});

				element.addEventListener("img-loaded", function () {
					masonryGrid.layout();
				});

				element.addEventListener("lazyloaded", function () {
					masonryGrid.layout();
				});

				element.addEventListener("elements-changed", function () {
					masonryGrid.reloadItems();
					masonryGrid.layout();
				});

				element.addEventListener("layoutComplete", function () {
					masonryGrid.layout();
				});
			}
		});
	}
})();
