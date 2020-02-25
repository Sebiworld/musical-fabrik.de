import { matches, ready, addClass, removeClass, closest } from "./helpers.js";

let s = function () {
    "use strict";

    const staticDefaults = {
        // If set, scrollinator will scroll to get the target into view plus the amount of pixels set:
        scrollOffset: 0,

        // Set to your DOMElement to watch for its height as scrolloffset. Can be set to an absolute amount of pixels, too:
        headerOffset: false,

        // Intercept every local link click and scroll to the position instead:
        activateLinkListener: true,

        alternativeTargetAttribute: "data-scrolltarget",

        // Classname which will be added to observed links in viewport:
        activeClass: "active",

        // Classname which will be added to active links:
        activeLinkClass: "active",

        // Classname which is added to sections which are currently in view.
        sectionInViewClass: "section-in-view",

        // Set to true to only allow a single section to be marked at active:
        onlySingleSectionActive: true,

        sectionSelector: 'section',

        // Writes the ID of the active section to the url:
        sectionToUrl: true,

        // Normally Scrollinator will check at browserwindow's top border for active elements (plus headerheight if set). 
        highlightOffsetTop: 40,

        observedLinkElements: {},

        observedElements: []
    };

    const easings = {
        linear(t) {
            return t;
        },
        easeInQuad(t) {
            return t * t;
        },
        easeOutQuad(t) {
            return t * (2 - t);
        },
        easeInOutQuad(t) {
            return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        },
        easeInCubic(t) {
            return t * t * t;
        },
        easeOutCubic(t) {
            return (--t) * t * t + 1;
        },
        easeInOutCubic(t) {
            return t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1;
        },
        easeInQuart(t) {
            return t * t * t * t;
        },
        easeOutQuart(t) {
            return 1 - (--t) * t * t * t;
        },
        easeInOutQuart(t) {
            return t < 0.5 ? 8 * t * t * t * t : 1 - 8 * (--t) * t * t * t;
        },
        easeInQuint(t) {
            return t * t * t * t * t;
        },
        easeOutQuint(t) {
            return 1 + (--t) * t * t * t * t;
        },
        easeInOutQuint(t) {
            return t < 0.5 ? 16 * t * t * t * t * t : 1 + 16 * (--t) * t * t * t * t;
        }
    };

    class Scrollinator {
        constructor(options) {
            const obj = this;
            if (window.scrollinator) {
                window.scrollinator.importOptions(options);
                return window.scrollinator;
            }

            // Set Default-Values:
            for (let option in staticDefaults) {
                obj['_' + option] = staticDefaults[option];
            }
            obj.importOptions(options);

            ready(function () {
                // Check if a hash-anchor is set initially in the url:
                // if (window.location.hash) {
                //     if (window.location.hash.indexOf("=") >= 0) return true;
                //     if (window.location.hash.indexOf("&") >= 0) {
                //         return true;
                //     }

                //     // Don't scroll if the user is already somewhere else on the page:
                //     const currentPosition =
                //         window.pageYOffset || document.documentElement.scrollTop;
                //     if (currentPosition > 10) {
                //         return true;
                //     }

                //     const target = document.querySelector(window.location.hash);
                //     if (target instanceof Element) {
                //         obj.scrollTo(target);
                //     }
                // }
            });

            obj._initialized = true;

            this._activateHashListener(this._activateLinkListener);
            this._refreshObservers();

            window.scrollinator = obj;
            return obj;
        }

        importOptions(options) {
            if (typeof options !== "object") {
                return false;
            }

            // Only import values which are definied in staticDefaults:
            for (let option in staticDefaults) {
                if (options[option] !== undefined) {
                    this[option] = options[option];
                }
            }
        }

        get scrollOffset() {
            return this._scrollOffset;
        }

        set scrollOffset(value) {
            if (typeof value !== "number") {
                return;
            }
            this._scrollOffset = value;
        }

        get headerOffset() {
            return this._headerOffset;
        }

        set headerOffset(value) {
            const obj = this;
            if (value !== false && typeof value !== 'number' && !(value instanceof Element)) {
                return;
            }
            this._headerOffset = value;

            if (!obj._isIntersectionObserverSupported()) {
                return;
            }

            if (this._linktargetObserver instanceof IntersectionObserver) {
                this._refreshObservers();
            }
        }

        _getHeaderOffsetValue() {
            if (typeof this.headerOffset === 'number') {
                return this.headerOffset;
            }
            if (!this.headerOffset || !(this.headerOffset instanceof Element)) {
                return 0;
            }

            return this.headerOffset.offsetHeight;
        }

        _getLinktargetObserverMargin() {
            return '-' + (this.scrollOffset + this._getHeaderOffsetValue()) + 'px 0px 0px 0px';
        }

        get activateLinkListener() {
            return this._activateLinkListener;
        }

        set activateLinkListener(value) {
            if (typeof value !== "boolean") {
                return;
            }
            this._activateHashListener(!!value);
            this._activateLinkListener = !!value;
        }

        getPositionTop(element) {
            let rect = element.getBoundingClientRect();
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            let position = rect.top + scrollTop;
            if (typeof position !== "number" || position < 0) {
                position = 0;
            }
            return position;
        }

        getPositionBottom(element) {
            let rect = element.getBoundingClientRect();
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            let position = rect.top + scrollTop + element.offsetHeight;
            if (typeof position !== "number" || position < 0) {
                position = 0;
            }
            return position;
        }

		/**
		 * Checks if a link leads to a target on the current page
		 */
        _isLocalLink(link) {
            if (typeof link !== 'string') {
                return false;
            }

            if (link.charAt(0) == "#") return true;

            const splittedLink = link.split("#");
            if (splittedLink.length > 0) {
                let linkAddress = splittedLink[0];

                if (window.location.pathname == linkAddress) {
                    return true;
                }
                if (linkAddress.charAt(linkAddress.length - 1) == "/" && linkAddress.length > 1) {
                    linkAddress = linkAddress.substring(0, linkAddress.length - 1);
                }
                if (linkAddress.length === 0) {
                    return true;
                }

                let currentAddress = (window.location.href + "").split("#")[0];
                if (typeof currentAddress !== 'string' || currentAddress.length < 1) {
                    return false;
                }

                if (currentAddress.charAt(aktuelleAdresse.length - 1) == "/") {
                    currentAddress = currentAddress.substring(0, currentAddress.length - 1);
                }

                if (linkAddress === currentAddress) {
                    return true;
                }

                const hrefWithoutProtocol = currentAddress.split("://");
                if (hrefWithoutProtocol.length >= 2 && linkAddress == hrefWithoutProtocol[1]) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Start listening for click-events on local links
         * @param {boolean} activate 
         */
        _activateHashListener(activate) {
            if (!this._initialized) {
                return false;
            }
            if (activate !== false) {
                document.addEventListener("click", this.hashListener.bind(this));
            }
            return true;
        }

        /**
         * Listener-function for clicks on links
         */
        hashListener(event) {
            const obj = this;

            if (!event.target || !(event.target instanceof Element)) {
                return true;
            }

            let element = event.target;
            if (!matches(element, '[href*="#"]:not([href="#"]):not([data-toggle])')) {
                element = closest(element, '[href*="#"]:not([href="#"]):not([data-toggle])');

                if (!element || !(element instanceof Element) || !matches(element, '[href*="#"]:not([href="#"]):not([data-toggle])')) {
                    return true;
                }
            }

            try {
                const targetElement = document.querySelector('#' + obj._getHashFromLink(element));
                obj.scrollTo(targetElement);
                event.preventDefault();
                return false;
            } catch (err) {

            }
            return true;
        }

        /**
         * Returns the linked hash-value of an element
         * @throws error if no valid local target found
         */
        _getHashFromLink(linkelement) {
            let obj = this;
            if (typeof linkelement === "string") {
                if (!obj._isLocalLink(linkelement)) {
                    throw "The url's target is not local";
                }

                const splittedLink = linkelement.split("#");
                if (splittedLink.length >= 2) {
                    return splittedLink[splittedLink.length - 1];
                }
            } else if (linkelement instanceof Element) {
                if (matches(linkelement, '[href*="#"]:not([href="#"]):not([data-toggle])')) {
                    return obj._getHashFromLink("" + linkelement.getAttribute("href"));
                } else if (matches(linkelement, '[' + obj.alternativeTargetAttribute + '*="#"]:not([' + obj.alternativeTargetAttribute + '="#"]):not([data-toggle])')) {
                    return obj._getHashFromLink("" + linkelement.getAttribute(obj.alternativeTargetAttribute));
                }
            }
            throw "No valid link-element found";
        }

        _calculateScrollPosition(targetElement) {
            return this.getPositionTop(targetElement) - this.scrollOffset - this._getHeaderOffsetValue();
        }

        /**
         * Scrolls smoothly to a specified Ttrget-element
         * @param {Element} targetElement
         * @param {number} duration 
         * @param {string} easing 
         */
        scrollTo(targetElement, duration = 500, easing = 'easeOutCubic') {
            const obj = this;

            return new Promise(function (resolve, reject) {
                if (!(targetElement instanceof Element) || typeof duration !== 'number' || typeof easing !== 'string' || typeof easings[easing] !== 'function') {
                    resolve(false);
                    return false;
                }

                const start = window.pageYOffset || document.documentElement.scrollTop;
                const startTime = 'now' in window.performance ? performance.now() : new Date().getTime();

                const documentHeight = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);
                const windowHeight = window.innerHeight || document.documentElement.clientHeight || document.getElementsByTagName('body')[0].clientHeight;
                let destinationOffset = obj._calculateScrollPosition(targetElement);
                let destinationOffsetToScroll = Math.round(documentHeight - destinationOffset < windowHeight ? documentHeight - windowHeight : destinationOffset);

                if ('requestAnimationFrame' in window === false) {
                    // No animation possible.
                    window.scroll(0, destinationOffsetToScroll);
                    resolve();
                    return;
                }

                let aborted = false;

                // Stop automatic scrolling when the user starts scrolling:
                const scrollEvents = [
                    "mousedown",
                    "wheel",
                    "DOMMouseScroll",
                    "mousewheel",
                    "keyup",
                    "touchmove",
                ];
                const scrollEventHandler = function (evt) {
                    removeScrollEventHandler();
                    aborted = true;
                };
                const removeScrollEventHandler = function () {
                    for (let evtname of scrollEvents) {
                        window.removeEventListener(evtname, scrollEventHandler);
                    }
                };

                for (let evtname of scrollEvents) {
                    window.addEventListener(evtname, scrollEventHandler, false);
                }

                function scroll() {
                    if (aborted) {
                        resolve(true);
                        return;
                    }

                    const now = 'now' in window.performance ? performance.now() : new Date().getTime();
                    const time = Math.min(1, ((now - startTime) / duration));
                    const timeFunction = easings[easing](time);

                    if (destinationOffset !== obj._calculateScrollPosition(targetElement)) {
                        destinationOffset = obj._calculateScrollPosition(targetElement);
                        destinationOffsetToScroll = Math.round(documentHeight - destinationOffset < windowHeight ? documentHeight - windowHeight : destinationOffset);
                    }

                    window.scroll(0, Math.ceil((timeFunction * (destinationOffsetToScroll - start)) + start));

                    if (window.pageYOffset === destinationOffsetToScroll) {
                        removeScrollEventHandler();
                        resolve(true)
                        return;
                    }

                    requestAnimationFrame(scroll);
                }

                scroll();
            });
        }

        // Highlighting of active links:
        get activeClass() {
            return this._activeClass;
        }

        set activeClass(value) {
            if (typeof value !== "string") {
                return;
            }
            this._activeClass = value;
            this._refreshObservers();
        }

        get activeLinkClass() {
            return this._activeLinkClass;
        }

        set activeLinkClass(value) {
            if (typeof value !== "string") {
                return;
            }
            this._activeLinkClass = value;
            this._refreshObservers();
        }

        get sectionInViewClass() {
            return this._sectionInViewClass;
        }

        set sectionInViewClass(value) {
            if (typeof value !== "string") {
                return;
            }
            this._sectionInViewClass = value;
            this._refreshObservers();
        }


        get alternativeTargetAttribute() {
            return this._alternativeTargetAttribute;
        }

        set alternativeTargetAttribute(value) {
            if (typeof value !== "string") {
                return;
            }
            this._alternativeTargetAttribute = value;
            this._refreshObservers();
        }

        _getLinkSelector() {
            return '[href*="#"]:not([href="#"]):not([data-toggle]), [' + this.alternativeTargetAttribute + '*="#"]:not([' + this.alternativeTargetAttribute + '="#"]):not([data-toggle])';
        }

        get sectionSelector() {
            return this._sectionSelector;
        }

        set sectionSelector(value) {
            if (typeof value !== "string") {
                return;
            }
            this._sectionSelector = value;
            this._refreshObservers();
        }

        get onlySingleSectionActive() {
            return this._onlySingleSectionActive;
        }

        set onlySingleSectionActive(value) {
            if (typeof value !== "boolean") {
                return;
            }
            this._onlySingleSectionActive = !!value;
            this._refreshObservers();
        }

        get sectionToUrl() {
            return this._sectionToUrl;
        }

        set sectionToUrl(value) {
            if (typeof value !== "boolean") {
                return;
            }
            this._sectionToUrl = !!value;
            this._refreshUrlHash();
        }

        get highlightOffset() {
            return this._highlightOffset;
        }

        set highlightOffset(value) {
            if (typeof value !== "number") {
                return;
            }
            this._highlightOffset = value;
        }

        get observedElements() {
            return this._observedElements;
        }

        set observedElements(elements) {
            const obj = this;
            obj._observedElements = [];
            obj.addObservedElements(elements);
        }

        addObservedElements(elements) {
            const obj = this;

            if (!obj._isIntersectionObserverSupported()) {
                return false;
            }

            if (typeof elements === 'string') {
                elements = document.querySelectorAll(elements);
            }

            if (elements instanceof NodeList || Array.isArray(elements)) {
                for (const index in elements) {
                    const element = elements[index];
                    if (!(element instanceof Element)) {
                        continue;
                    }
                    obj.addObservedElement(element);
                }
            }
        }

        addObservedElement(element) {
            const obj = this;

            if (!obj._isIntersectionObserverSupported()) {
                return false;
            }

            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (!(element instanceof Element)) {
                return false;
            }


            if (!Array.isArray(obj._observedElements)) {
                obj._observedElements = [];
            }

            if (obj._observedElements.indexOf(element) >= 0) {
                // Element is already observed
                return true;
            }

            obj._observedElements.push(element);

            if (obj._linktargetObserver instanceof IntersectionObserver) {
                obj._linktargetObserver.observe(element);
            } else {
                obj._refreshObservers();
            }
        }

        removeObservedElement(element) {
            const obj = this;

            if (!obj._isIntersectionObserverSupported()) {
                return false;
            }

            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (!(element instanceof Element)) {
                return false;
            }

            if (!Array.isArray(obj._observedElements)) {
                return true;
            }

            const elementIndex = obj._observedElements.indexOf(element);
            if (elementIndex < 0) {
                // Element is not observed
                return true;
            }

            obj._observedElements.splice(elementIndex, 1);
            if (obj._linktargetObserver instanceof IntersectionObserver) {
                obj._linktargetObserver.unobserve(element);
            } else {
                obj._refreshObservers();
            }
        }

        get observedLinkElements() {
            return this._observedLinkElements;
        }

        set observedLinkElements(elements) {
            const obj = this;
            obj._observedLinkElements = {};
            obj.addObservedLinkElements(elements);
        }

        addObservedLinkElements(elements) {
            const obj = this;

            if (!obj._isIntersectionObserverSupported()) {
                return false;
            }

            if (typeof elements === 'string') {
                elements = document.querySelectorAll(elements);
            }

            if (elements instanceof NodeList || Array.isArray(elements)) {
                for (const index in elements) {
                    const linkElement = elements[index];
                    if (!(linkElement instanceof Element)) {
                        continue;
                    }
                    obj.addObservedLinkElement(linkElement);
                }
            }
        }

        addObservedLinkElement(element, targetSelector) {
            const obj = this;

            if (!obj._isIntersectionObserverSupported()) {
                return false;
            }

            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (!(element instanceof Element)) {
                return false;
            }

            if (typeof targetSelector !== 'string') {
                try {
                    targetSelector = '#' + obj._getHashFromLink(element);
                } catch (err) {
                    return false;
                }
            }

            if (!Array.isArray(obj._observedLinkElements[targetSelector])) {
                obj._observedLinkElements[targetSelector] = [];
            }

            if (obj._observedLinkElements[targetSelector].indexOf(element) >= 0) {
                // Element is already observed
                return true;
            }

            obj._observedLinkElements[targetSelector].push(element);

            if (obj._linktargetObserver instanceof IntersectionObserver) {
                const targetElement = document.querySelector(targetSelector);
                if (targetElement instanceof Element) {
                    targetElement.setAttribute('data-scrollinator-selector', targetSelector);
                    obj._linktargetObserver.observe(targetElement);
                }
            } else {
                obj._refreshObservers();
            }
        }

        removeObservedLinkElement(element) {
            const obj = this;

            if (!obj._isIntersectionObserverSupported()) {
                return false;
            }

            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (!(element instanceof Element)) {
                return false;
            }

            let targetSelector = false;
            try {
                targetSelector = '#' + obj._getHashFromLink(element);
            } catch (err) {
                return false;
            }

            if (!Array.isArray(obj._observedLinkElements[targetSelector])) {
                return true;
            }

            const elementIndex = obj._observedLinkElements[targetSelector].indexOf(element);
            if (elementIndex < 0) {
                // Element is not observed
                return true;
            }

            obj._observedLinkElements[targetSelector].splice(elementIndex, 1);
            if (obj._observedLinkElements[targetSelector].length < 1) {
                delete obj._observedLinkElements[targetSelector];
            }

            if (obj._linktargetObserver instanceof IntersectionObserver) {
                try {
                    const targetElement = document.querySelector(targetSelector);
                    if (targetElement instanceof Element) {
                        targetElement.removeAttribute('data-scrollinator-selector');
                        obj._linktargetObserver.unobserve(targetElement);
                    }
                } catch (err) { }
            } else {
                obj._refreshObservers();
            }
        }

        _refreshObservers() {
            const obj = this;
            if (!obj._initialized) {
                return false;
            }

            if (!obj._isIntersectionObserverSupported()) {
                return false;
            }

            if (obj._linktargetObserver instanceof IntersectionObserver) {
                // Disable old observer instance
                obj._linktargetObserver.disconnect();
            }

            obj._linktargetObserver = new IntersectionObserver((entries, observer) => {
                // This function is called every time an observed element changes its visibility
                for (const index in entries) {
                    const entry = entries[index];
                    if (!(entry.target instanceof Element)) {
                        continue;
                    }

                    const selector = entry.target.getAttribute('data-scrollinator-selector');
                    const linkElements = obj._observedLinkElements[selector];

                    if (entry.intersectionRatio > 0) {
                        // element is in viewport

                        if (matches(entry.target, obj.sectionSelector)) {
                            addClass(entry.target, obj.sectionInViewClass);
                        }

                        if (!matches(entry.target, obj.sectionSelector) || !obj.onlySingleSectionActive) {
                            addClass(entry.target, obj.activeClass);
                            if (Array.isArray(linkElements)) {
                                for (const linkElement of linkElements) {
                                    addClass(linkElement, obj.activeLinkClass);
                                }
                            }
                        }
                        continue;
                    }

                    // element is not in viewport
                    removeClass(entry.target, obj.activeClass);
                    removeClass(entry.target, obj.sectionInViewClass);
                    if (Array.isArray(linkElements)) {
                        for (const linkElement of linkElements) {
                            removeClass(linkElement, obj.activeLinkClass);
                        }
                    }
                }

                if (obj.onlySingleSectionActive) {
                    obj._refreshActiveSection();
                }

                if (obj.sectionToUrl) {
                    obj._refreshUrlHash();
                }

            }, {
                root: null,
                rootMargin: obj._getLinktargetObserverMargin()
            }
            );

            for (const selector in obj._observedLinkElements) {
                const targetElement = document.querySelector(selector);
                if (targetElement instanceof Element) {
                    targetElement.setAttribute('data-scrollinator-selector', selector);
                    obj._linktargetObserver.observe(targetElement);
                }
            }

            for (const index in obj._observedElements) {
                const element = obj._observedElements[index];
                if (element instanceof Element) {
                    obj._linktargetObserver.observe(element);
                }
            }

        }

        _refreshActiveSection() {
            const obj = this;
            const sections = document.querySelectorAll('.' + obj.sectionInViewClass);

            let firstFlag = true;
            for (const si in sections) {
                const section = sections[si];
                if (!(section instanceof Element)) {
                    continue;
                }
                const selector = section.getAttribute('data-scrollinator-selector');
                const linkElements = obj._observedLinkElements[selector];

                if (firstFlag) {
                    firstFlag = false;
                    addClass(section, obj.activeClass);
                    if (Array.isArray(linkElements)) {
                        for (const linkElement of linkElements) {
                            addClass(linkElement, obj.activeLinkClass);
                        }
                    }
                    continue;
                }

                removeClass(section, obj.activeClass);
                if (Array.isArray(linkElements)) {
                    for (const linkElement of linkElements) {
                        removeClass(linkElement, obj.activeLinkClass);
                    }
                }
            }
        }

        /**
         * Looks for the first active <section> and writes its hash into url
         */
        _refreshUrlHash() {
            const obj = this;

            if (obj.sectionToUrl) {
                const activeSection = document.querySelector(obj.sectionSelector + '.' + obj.activeClass);
                const activeHash = window.location.hash;
                if (activeSection instanceof Element) {
                    const sectionSelector = activeSection.getAttribute('data-scrollinator-selector');
                    if (typeof sectionSelector === 'string' && sectionSelector.charAt(0) === '#') {
                        if (sectionSelector !== activeHash) {
                            window.history.replaceState(
                                {},
                                document.title,
                                window.location.pathname + window.location.search + sectionSelector
                            );
                        }
                        return true;
                    }
                }
            }

            // Else: Remove Hash from url
            window.history.replaceState(
                {},
                document.title,
                window.location.pathname + window.location.search
            );
        }

        _isIntersectionObserverSupported() {
            return 'IntersectionObserver' in window && 'IntersectionObserverEntry' in window && 'intersectionRatio' in window.IntersectionObserverEntry.prototype;
        }

    }

    return Scrollinator;
};
export default s();
