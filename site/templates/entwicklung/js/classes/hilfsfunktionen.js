import {indexOf} from "lodash";

export const ready = function(fn) {
	if (document.attachEvent ? document.readyState === "complete" : document.readyState !== "loading"){
		fn();
	} else {
		document.addEventListener('DOMContentLoaded', fn);
	}
}

export const matches = function(el, selector) {
	return (el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector).call(el, selector);
};

export const addClass = function(el, className){
	if (el.classList){
		el.classList.add(className);
	}else if(el.className !== undefined){
		el.className += ' ' + className;
	}else{
		el.className = className;
	}
}

export const hasClass = function(el, className){
	if (el.classList){
		return el.classList.contains(className);
	}
	return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
}

export const removeClass = function(el, className){
	if (el.classList){
		el.classList.remove(className);
	}else if(el.className !== undefined){
		el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
	}
}

export const trigger = function(el, eventName, data){
	if(typeof data !== 'Object'){
		data = {};
	}

	const nativeEvents = ['change', 'resize', 'scroll'];

	let event;
	if(indexOf(nativeEvents, eventName) >= 0){
		// NativesEvent
		event = document.createEvent('HTMLEvents');
		event.initEvent(eventName, true, false, data);
		// }else if (window.CustomEvent) {
		// // Hier funktioniert das Event-Bubbling irgendwie nicht richtig...
		// 	console.log("new Custom Event!");
		// 	event = new CustomEvent(eventName, data);
	}else{
		event = document.createEvent('CustomEvent');
		event.initCustomEvent(eventName, true, true, data);
	}

	el.dispatchEvent(event);

	return el;
}

export const createElementFromHTML = function(htmlString) {
	var div = document.createElement('div');
	div.innerHTML = htmlString.trim();

	// Change this to div.childNodes to support multiple top-level nodes
	return div.firstChild;
};

// export const propertyA = "A";
export const removeElements = function(elements){
	if(elements.length < 1){
		return false;
	}
	for(let index in elements){
		const item = elements[index];
		if(typeof item !== 'object' || !(item instanceof Element)){
			continue;
		}
		item.parentNode.removeChild(item);
	}
	return true;
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