/* jshint -W024 */
// import "@babel/polyfill";
import { ready, addClass, removeClass, hasClass, closest } from "./classes/helpers.js";
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
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/util');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/alert');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/button');
	// await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/carousel');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/collapse');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/dropdown');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/modal');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/popover');
	// await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/scrollspy');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/tab');
	// await import (/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/toast');
	await import(/* webpackChunkName: "bootstrap-js" */ 'bootstrap/js/src/tooltip');

	window.lazySizesConfig = window.lazySizesConfig || {};
	window.lazySizesConfig.init = false;
	const { ImageHandler: ImageHandler } = await import(/* webpackChunkName: "image-handler" */ './classes/ImageHandler');

	ready(function () {
		// Hamburgericon-Animation:
		const hamburgerDropdowns = document.querySelectorAll('.dropdown.has-hamburger');
		for (const hamburgerDropdown of hamburgerDropdowns) {
			if (!hamburgerDropdown || typeof hamburgerDropdown !== 'object' || !(hamburgerDropdown instanceof Element)) {
				continue;
			}

			const childElement = hamburgerDropdown.querySelector('.hamburger.dropdown-toggle');
			if (!childElement || typeof childElement !== 'object' || !(childElement instanceof Element)) {
				continue;
			}

			hamburgerDropdown.addEventListener('show.bs.dropdown"', function () {
				addClass(childElement, 'is-active');
			});
			hamburgerDropdown.addEventListener('hide.bs.dropdown', function () {
				removeClass(childElement, 'is-active');
			});
		}

		// Collapse:
		const hamburgerCollapses = document.querySelectorAll('.collapse.has-hamburger[id]');
		for (const hamburgerCollapse of hamburgerCollapses) {
			if (!hamburgerCollapse || typeof hamburgerCollapse !== 'object' || !(hamburgerCollapse instanceof Element)) {
				continue;
			}
			const id = hamburgerCollapse.getAttribute('id');
			if (!id) { continue; }

			const childElement = document.querySelector('.hamburger[data-toggle="collapse"][data-target="#' + id + '"]');
			if (!childElement || typeof childElement !== 'object' || !(childElement instanceof Element)) {
				continue;
			}

			hamburgerCollapse.addEventListener('show.bs.collapse', function () {
				addClass(childElement, 'is-active');
			});
			hamburgerCollapse.addEventListener('hide.bs.collapse', function () {
				removeClass(childElement, 'is-active');
			});
		}

		ImageHandler.init();
	});
})();
