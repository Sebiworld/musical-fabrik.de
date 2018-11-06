import { trigger, ready } from "./classes/hilfsfunktionen.js";
import { throttle } from "lodash";

var headerElement = $("body>header");
var didScroll = false;
var changeHeaderOn = 300;

function init() {
  if (typeof throttle === "function") {
    // wenn lodash verfügbar ist, wird die throttle-Funktion genutzt (spart Rechenleistung)
    $(window).on(
      "scroll",
      throttle(function() {
        if (!didScroll) {
          didScroll = true;
          setTimeout(scrollPage, 250);
        }
      }, 200)
    );
  } else {
    $(window).on("scroll", function() {
      if (!didScroll) {
        didScroll = true;
        setTimeout(scrollPage, 250);
      }
    });
  }
  scrollPage();
}

function scrollPage() {
  var aktuellePosition = $(window).scrollTop();
  if (aktuellePosition >= changeHeaderOn + 100) {
    headerElement.addClass("minimiert");
    $(".back-to-top").fadeIn();
  } else if (aktuellePosition < changeHeaderOn) {
    headerElement.removeClass("minimiert");
    $(".back-to-top").fadeOut();
  }
  didScroll = false;
}

ready(function() {
  init();
});
