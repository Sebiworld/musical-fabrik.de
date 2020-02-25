import { ready, addClass, hasClass } from "./classes/helpers.js";
import Rellax from 'rellax/rellax.min';

(async () => {
	const parallaxElements = document.querySelectorAll(".parallax-background, .parallax-element");

	// console.log("UserAgent: ", navigator.userAgent);
	if (parallaxElements.length > 0) {
		function loadParallax(element) {
			addClass(element, "parallax-loaded");

			let options = {
				speed: 2,
			};

			if (
				element.getAttribute("data-parallax-speed") &&
				!isNaN(parseFloat(element.getAttribute("data-parallax-speed")))
			) {
				options.speed = parseFloat(element.getAttribute("data-parallax-speed"));
			}

			if (element.getAttribute("data-parallax-type")) {
				options.type = element.getAttribute("data-parallax-type");
			}

			if (element.getAttribute("data-parallax-threshold")) {
				options.threshold = element.getAttribute("data-parallax-threshold");
			}

			new Rellax(element, options);
		}

		ready(function() {
			for (let index in parallaxElements) {
				let element = parallaxElements[index];
				if (typeof element !== "object" || !(element instanceof Element)) {
					continue;
				}

				loadParallax(element);
				if (hasClass(element, "progressive__bg")) {
					element.addEventListener("img-loaded", function() {
						loadParallax(element);
					});
				}
			}
		});
	}
})();
