import 'core-js/modules/es.array.iterator';
import 'core-js/modules/es.object.to-string';
import 'core-js/modules/es.promise';

import 'picturefill/dist/picturefill.min.js';

/**
 * Safari 10.1 supports modules, but does not support the `nomodule` attribute - it will
 * load <script nomodule> anyway. This snippet solve this problem, but only for script
 * tags that load external code, e.g.: <script nomodule src="nomodule.js"></script>
 *
 * Again: this will **not** prevent inline script, e.g.:
 * <script nomodule>alert('no modules');</script>.
 *
 * This workaround is possible because Safari supports the non-standard 'beforeload' event.
 * This allows us to trap the module and nomodule load.
 *
 * Note also that `nomodule` is supported in later versions of Safari - it's just 10.1 that
 * omits this attribute.
 */
(function () {
    var check = document.createElement('script');
    if (!('noModule' in check) && 'onbeforeload' in check) {
        var support = false;
        document.addEventListener('beforeload', function (e) {
            if (e.target === check) {
                support = true;
            } else if (!e.target.hasAttribute('nomodule') || !support) {
                return;
            }
            e.preventDefault();
        }, true);

        check.type = 'module';
        check.src = '.';
        document.head.appendChild(check);
        check.remove();
    }
}());

import 'whatwg-fetch';
// import 'classlist-polyfill';

// Polyfill für closest-Funktion:
if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector ||
        Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
    Element.prototype.closest = function (s) {
        var el = this;

        do {
            if (el.matches(s)) return el;
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}