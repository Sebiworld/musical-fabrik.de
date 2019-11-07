import { ready, addClass, removeClass } from "./helpers.js";
import { throttle } from "lodash-es";

(async () => {
  let headerElement = document.querySelector('body>header');
  if (typeof headerElement !== 'object' || !(headerElement instanceof Element)) {
    headerElement = false;
  }

  let backToTopElement = document.querySelector('.back-to-top');
  if (typeof backToTopElement !== 'object' || !(backToTopElement instanceof Element)) {
    backToTopElement = false;
  }

  function onScroll() {

    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    if (scrollTop >= 400) {
      if (headerElement) {
        addClass(headerElement, 'mini');
      }
      if (backToTopElement) {
        addClass(backToTopElement, 'show');
        removeClass(backToTopElement, 'hide');
      }
    } else if (scrollTop < 300) {
      if (headerElement) {
        removeClass(headerElement, 'mini');
      }
      if (backToTopElement) {
        addClass(backToTopElement, 'hide');
        removeClass(backToTopElement, 'show');
      }
    }
  }

  ready(function () {
    document.addEventListener('scroll', throttle(onScroll, 200));
    onScroll();
  });
})();
