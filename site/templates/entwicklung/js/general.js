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
	const progressively = await import("progressively");
	const popper = await import("popper.js");
	const bootstrap = await import("bootstrap");

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
