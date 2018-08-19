import { trigger, ready } from "./classes/hilfsfunktionen.js";
import ImagesLoaded from 'imagesloaded';
import Masonry from 'masonry-layout';

(async () => {
	const elemente = document.querySelectorAll(".masonry-grid");
	if (elemente.length > 0) {

		ready(function() {
			for (let index in elemente) {
				const element = elemente[index];
				if (typeof element !== "object" || !(element instanceof Element)) {
					continue;
				}

				let masonryGrid = new Masonry(element, {
					itemSelector: ".masonry-grid-item",
					columnWidth: ".masonry-grid-sizer",
					percentPosition: true,
				});

				ImagesLoaded(element).on("progress", function() {
					masonryGrid.layout();
				});

				element.addEventListener("bildGeladen", function() {
					masonryGrid.layout();
				});

				element.addEventListener("elementeGeaendert", function() {
					masonryGrid.reloadItems();
					masonryGrid.layout();
				});

				element.addEventListener("layoutGeaendert", function() {
					masonryGrid.layout();
				});
			}
		});
	}
})();
