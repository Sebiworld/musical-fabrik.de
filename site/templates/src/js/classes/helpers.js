import { indexOf, random } from "lodash-es";

export const ready = function (fn) {
	if (document.attachEvent ? document.readyState === "complete" : document.readyState !== "loading") {
		fn();
	} else {
		document.addEventListener('DOMContentLoaded', fn);
	}
};

export const matches = function (el, selector) {
	return (el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector).call(el, selector);
};

export const closest = function (el, selector) {
	if (Element.prototype.closest) {
		return el.closest(selector);
	}
	do {
		if (matches(el, selector)) return el;
		el = el.parentElement || el.parentNode;
	} while (el !== null && el.nodeType === 1);
	return null;
};

export const addClass = function (el, className) {
	if (hasClass(el, className)) {
		return true;
	}

	if (el.classList) {
		el.classList.add(className);
	} else if (el.className !== undefined) {
		el.className += ' ' + className;
	} else {
		el.className = className;
	}
};

export const hasClass = function (el, className) {
	if (el.classList) {
		return el.classList.contains(className);
	}
	return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
};

export const removeClass = function (el, className) {
	if (!hasClass(el, className)) {
		return true;
	}
	if (el.classList) {
		el.classList.remove(className);
	} else if (el.className !== undefined) {
		el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
	}
};

export const trigger = function (el, eventName, data) {
	if (typeof data !== 'object') {
		data = {};
	}

	const nativeEvents = ['change', 'resize', 'scroll'];

	let event;
	if (indexOf(nativeEvents, eventName) >= 0) {
		// NativesEvent
		event = document.createEvent('HTMLEvents');
		event.initEvent(eventName, true, false, data);
		// }else if (window.CustomEvent) {
		// // Hier funktioniert das Event-Bubbling irgendwie nicht richtig...
		// 	console.log("new Custom Event!");
		// 	event = new CustomEvent(eventName, data);
	} else {
		event = document.createEvent('CustomEvent');
		event.initCustomEvent(eventName, true, true, data);
	}

	el.dispatchEvent(event);

	return el;
};

export const createElementFromHTML = function (htmlString) {
	var div = document.createElement('div');
	div.innerHTML = htmlString.trim();

	// Change this to div.childNodes to support multiple top-level nodes
	return div.firstChild;
};

// export const propertyA = "A";
export const removeElements = function (elements) {
	if (elements.length < 1) {
		return false;
	}
	for (let index in elements) {
		const item = elements[index];
		if (typeof item !== 'object' || !(item instanceof Element)) {
			continue;
		}
		item.parentNode.removeChild(item);
	}
	return true;
};

export const timeout = function (ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
};

/**
 * https://github.com/cferdinandi/nextUntil
 */
export const nextUntil = function (elem, selector, filter) {
	// matches() polyfill
	if (!Element.prototype.matches) {
		Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
	}

	// Setup siblings array
	var siblings = [];

	// Get the next sibling element
	elem = elem.nextElementSibling;

	// As long as a sibling exists
	while (elem) {
		// If we've reached our match, bail
		if (elem.matches(selector)) break;

		// If filtering by a selector, check if the sibling matches
		if (filter && !elem.matches(filter)) {
			elem = elem.nextElementSibling;
			continue;
		}

		// Otherwise, push it to the siblings array
		siblings.push(elem);

		// Get the next sibling element
		elem = elem.nextElementSibling;
	}

	return siblings;
};

export const formatMoney = function (n, c, d, t) {
	var c = isNaN(c = Math.abs(c)) ? 2 : c,
		d = d == undefined ? "," : d,
		t = t == undefined ? "." : t,
		s = n < 0 ? "-" : "",
		i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
		j = (j = i.length) > 3 ? j % 3 : 0;

	return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

export const slugify = function (string) {
	const a = 'àáäâãåăæąçćčđďèéěėëêęğǵḧìíïîįłḿǹńňñòóöôœøṕŕřßşśšșťțùúüûǘůűūųẃẍÿýźžż·/_,:;'
	const b = 'aaaaaaaaacccddeeeeeeegghiiiiilmnnnnooooooprrsssssttuuuuuuuuuwxyyzzz------'
	const p = new RegExp(a.split('').join('|'), 'g')

	return string.toString().toLowerCase()
		.replace(/\s+/g, '-') // Replace spaces with -
		.replace(p, c => b.charAt(a.indexOf(c))) // Replace special characters
		.replace(/&/g, '-and-') // Replace & with 'and'
		.replace(/[^\w\-]+/g, '') // Remove all non-word characters
		.replace(/\-\-+/g, '-') // Replace multiple - with single -
		.replace(/^-+/, '') // Trim - from start of text
		.replace(/-+$/, ''); // Trim - from end of text
};

export const setIntervalAsync = (fn, msmin, msmax) => {
	fn().then(() => {
		let ms = msmin;
		if (typeof msmin === 'number' && typeof msmax === 'number') ms = random(ms, msmax);
		setTimeout(() => setIntervalAsync(fn, msmin, msmax), ms);
	});
};