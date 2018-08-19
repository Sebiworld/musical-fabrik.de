import { ready, addClass, hasClass } from "./classes/hilfsfunktionen.js";
import Rellax from 'rellax/rellax.min';

(async () => {
	const parallaxElemente = document.querySelectorAll(".parallax-hintergrund, .parallax-element");

	// console.log("UserAgent: ", navigator.userAgent);
	if (parallaxElemente.length > 0) {
		function parallaxLaden(element) {
			addClass(element, "parallax-geladen");

			let options = {
				speed: 2,
			};

			if (
				element.getAttribute("data-parallax-speed") &&
				parseFloat(element.getAttribute("data-parallax-speed")) !== NaN
			) {
				options.speed = parseFloat(element.getAttribute("data-parallax-speed"));
			}

			if (element.getAttribute("data-parallax-type")) {
				options.type = element.getAttribute("data-parallax-type");
			}

			if (element.getAttribute("data-parallax-threshold")) {
				options.threshold = element.getAttribute("data-parallax-threshold");
			}

			const newRellax = new Rellax(element, options);
		}

		ready(function() {
			for (let index in parallaxElemente) {
				let element = parallaxElemente[index];
				if (typeof element !== "object" || !(element instanceof Element)) {
					continue;
				}

				parallaxLaden(element);
				if (hasClass(element, "progressive__bg")) {
					element.addEventListener("bildGeladen", function() {
						parallaxLaden(element);
					});
				}
			}
		});
	}
})();
