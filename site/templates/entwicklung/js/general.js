// import "@babel/polyfill";
import { trigger, ready } from "./classes/hilfsfunktionen.js";
import Scrollinator from "./classes/Scrollinator.js";

// Scrollinator.getInstance({
// 	ermittleHeaderHoehe: false,
// 	scrollOffset: 90,
// });

const scrollinator = new Scrollinator({
	ermittleHeaderHoehe: false,
	scrollOffset: 90,
});

// Bootstrap wird nachgeladen:
(async () => {
	const progressively = await import(/* webpackChunkName: "progressively" */ "progressively");
	
	await import (/* webpackChunkName: "bs-util" */ 'bootstrap/js/src/util');
	await import (/* webpackChunkName: "bs-alert" */ 'bootstrap/js/src/alert');
	await import (/* webpackChunkName: "bs-button" */ 'bootstrap/js/src/button');
	// await import (/* webpackChunkName: "bs-carousel" */ 'bootstrap/js/src/carousel');
	await import (/* webpackChunkName: "bs-collapse" */ 'bootstrap/js/src/collapse');
	await import (/* webpackChunkName: "bs-dropdown" */ 'bootstrap/js/src/dropdown');
	await import (/* webpackChunkName: "bs-modal" */ 'bootstrap/js/src/modal');
	await import (/* webpackChunkName: "bs-popover" */ 'bootstrap/js/src/popover');
	// await import (/* webpackChunkName: "bs-scrollspy" */ 'bootstrap/js/src/scrollspy');
	await import (/* webpackChunkName: "bs-tab" */ 'bootstrap/js/src/tab');
	// await import (/* webpackChunkName: "bs-toast" */ 'bootstrap/js/src/toast');
	await import (/* webpackChunkName: "bs-tooltip" */ 'bootstrap/js/src/tooltip');

	ready(function() {
		// Progressively zum Nachladen von Bildern:
		progressively.init({
			onLoad: function(elem) {
				trigger(elem, "bildGeladen");
			},
		});

		$('a[data-toggle="pill"], a[data-toggle="tab"]').on("shown.bs.tab", function(e) {
			progressively.render();
		});

		// Hamburgericon-Animation:
		// Dropdowns:
		$(".dropdown.has-hamburger").on("show.bs.dropdown", function() {
			$(this)
				.find(".hamburger.dropdown-toggle")
				.addClass("is-active");
		});
		$(".dropdown.has-hamburger").on("hide.bs.dropdown", function() {
			$(this)
				.find(".hamburger.dropdown-toggle")
				.removeClass("is-active");
		});

		// Collapse:
		$(".collapse.has-hamburger[id]").on("show.bs.collapse", function() {
			$(
				'.hamburger[data-toggle="collapse"][data-target="#' +
					$(this).attr("id") +
					'"]'
			).addClass("is-active");
		});
		$(".collapse.has-hamburger[id]").on("hide.bs.collapse", function() {
			$(
				'.hamburger[data-toggle="collapse"][data-target="#' +
					$(this).attr("id") +
					'"]'
			).removeClass("is-active");
		});

		// Karten sollen auf Klick aktiv bleiben. Nur mit Hover sind sie auf Touch-Geräten sonst nicht bedienbar.
		$(document).on("click touchstart", ".card", function() {
			if ($(this).hasClass("aktiv")) {
				$(this).removeClass("aktiv");
				$(this).addClass("nicht-aktiv");
			} else {
				$(this).addClass("aktiv");
				$(this).removeClass("nicht-aktiv");
			}
		});
	});
})();
