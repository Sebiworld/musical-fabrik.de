/* jshint -W024 */
// import "@babel/polyfill";
import { ready } from "./classes/helpers.js";
import Scrollinator from "./classes/Scrollinator.js";
import './classes/animated-header';
import './classes/events-box';

const scrollinator = new Scrollinator({
	scrollOffset: 40,
	headerOffset: document.querySelector('header>nav')
});

scrollinator.addObservedLinkElements(".highlight-navigation .nav-item, .highlight-navigation .dropdown-menu .dropdown-item");
scrollinator.addObservedElements('section');

// Bootstrap will be reloaded:
(async () => {
	// const progressively = await import(/* webpackChunkName: "progressively" */ "progressively");
	
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/util');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/alert');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/button');
	// await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/carousel');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/collapse');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/dropdown');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/modal');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/popover');
	// await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/scrollspy');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/tab');
	// await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/toast');
	await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/tooltip');

	ready(function() {
		// Progressively for dynamically loading images:
		// progressively.init({
		// 	onLoad: function(elem) {
		// 		trigger(elem, "img-loaded");
		// 	},
		// });

		// $('a[data-toggle="pill"], a[data-toggle="tab"]').on("shown.bs.tab", function(e) {
		// 	progressively.render();
		// });

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

		// Cards should remain active at the click of a mouse. Only with Hover are they otherwise not operable on touch devices.
		$(document).on("click touchstart", ".card", function() {
			if ($(this).hasClass("active")) {
				$(this).removeClass("active");
				$(this).addClass("not-active");
			} else {
				$(this).addClass("active");
				$(this).removeClass("not-active");
			}
		});
	});
})();
