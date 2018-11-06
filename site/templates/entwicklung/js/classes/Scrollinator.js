import anime from "animejs";
import {
	matches,
	ready,
	addClass,
	removeClass,
	hasClass,
	closest,
} from "./hilfsfunktionen.js";
import { throttle, remove } from "lodash";

let s = function() {
	"use strict";

	let staticDefaults = {
		scrollOffset: 0,

		// Link-Klicks abfangen und bearbeiten, sofern es sich um einen lokalen Link handelt:
		activateLinkListener: true,

		// Soll die Header-Höhe automatisch als Offset berücksichtigt werden?
		ermittleHeaderHoehe: true,
		headerHoeheSelector: "header>nav",

		// Sollen Menüelemente, deren Zielelement sichtbar ist, gekennzeichnet werden?
		navigationHighlighting: true,

		// Zu überwachende Link-Elemente:
		highlightSelektor:
			".highlight-navigation ul.nav a.nav-link, .highlight-navigation .dropdown-menu .dropdown-item",

		// Die Klassse, die aktiven Elementen hinzugefügt wird:
		aktivKlasse: "active",

		// Soll das Parent-Element des Links mit der Aktiv-Klasse versehen werden?
		aktiviereParent: false,
		aktiviereParentSelector: false,
	};

	class Scrollinator {
		constructor(options) {
			if (window.scrollinatorInstanz) {
				window.scrollinatorInstanz.importiere(options);
				return window.scrollinatorInstanz;
			}
			window.scrollinatorInstanz = this;

			let obj = this;
			if (typeof options !== "object") {
				options = {};
			}

			// Wenn nicht in options vorhanden: Standardwerte benutzen:
			for (let option in staticDefaults) {
				if (options[option] !== undefined) {
					this[option] = options[option];
				} else {
					this[option] = staticDefaults[option];
				}
			}

			// Speicherung aller auf dieser Seite behandelten Sektions-Hashwerte:
			this._sektionen = {};
			this._aktiverHash = "";

			ready(function() {
				// Wenn NavigationHighlighting aktiviert ist, soll es hier noch einmal explizit getriggert werden, da jetzt alle anderen Parameter zur Verfügung stehen:
				if (obj.navigationHighlighting) {
					obj.navigationHighlighting = true;
				}

				// Wird aufgerufen, wenn beim Seitenaufruf ein Ankerlink in der URL steht:
				if (window.location.hash) {
					if (window.location.hash.indexOf("=") >= 0) return true;
					if (window.location.hash.indexOf("&") >= 0) {
						return true;
					}

					// Wenn der User sich schon irgendwo auf der Seite befindet, soll initial nicht zum Hashwert gescrollt werden.
					const aktuellePosition =
						window.pageYOffset || document.documentElement.scrollTop;
					if (aktuellePosition > 10) {
						return true;
					}

					const zielElement = document.querySelector(window.location.hash);
					if (typeof zielElement === "object" && zielElement) {
						obj.hinscrollen(zielElement);
					}
				}
			});

			this._initialisiert = true;
		}

		static getInstance(options) {
			// console.log("getInstance", options, scrollinatorInstanz);
			// debugger;
			// if (!scrollinatorInstanz) {
				scrollinatorInstanz = new Scrollinator(options);
			// 	console.log("Instanz gesetzt.", scrollinatorInstanz);
			// } else {
			// 	scrollinatorInstanz.importiere(options);
			// }
			return scrollinatorInstanz;
		}

		importiere(options) {
			if (typeof options !== "object") {
				return false;
			}

			// Wenn nicht in options vorhanden: Standardwerte benutzen:
			for (let option in staticDefaults) {
				if (options[option] !== undefined) {
					this[option] = options[option];
				} else {
					this[option] = staticDefaults[option];
				}
			}
		}

		get scrollOffset() {
			if (this._scrollOffset === undefined) {
				this._scrollOffset = staticDefaults.scrollOffset;
			}

			if (typeof this._headerHoehe !== "number") {
				this._headerHoehe = 0;
			}

			return this._scrollOffset + this._headerHoehe;
		}

		set scrollOffset(wert) {
			if (typeof wert !== "number") {
				return false;
			}
			this._scrollOffset = wert;
		}

		get ermittleHeaderHoehe() {
			if (this._ermittleHeaderHoehe === undefined) {
				this.ermittleHeaderHoehe = staticDefaults.ermittleHeaderHoehe;
			}
			return this._ermittleHeaderHoehe;
		}

		set ermittleHeaderHoehe(wert) {
			this._ermittleHeaderHoehe = wert;
			this.refreshHeaderHoehe();
		}

		get headerHoeheSelector() {
			if (this._headerHoeheSelector === undefined) {
				this.headerHoeheSelector = staticDefaults.headerHoeheSelector;
			}
			return this._headerSelector;
		}

		set headerHoeheSelector(wert) {
			if (typeof wert !== "string") {
				return false;
			}
			this._headerSelector = wert;
			this.refreshHeaderHoehe();

			return this._headerSelector;
		}

		set activateLinkListener(wert) {
			if (wert) {
				this.aktiviereHashListener();
			} else {
				this.deaktiviereHashListener();
			}
		}

		/**
		 * Liest die Höhe des angegebenen Header-Elements aus, und setzt sie als Offset ein.
		 */
		refreshHeaderHoehe() {
			const obj = this;
			// TODO automatischer Refresh funktioniert noch nicht...
			return true;
			obj._headerHoehe = 0;

			if (!obj.ermittleHeaderHoehe) {
				return false;
			}

			if (
				typeof obj.headerHoeheSelector !== "string" ||
				obj.headerHoeheSelector.length < 1
			) {
				return false;
			}

			let headerElement = document.querySelector(obj.headerHoeheSelector);
			if (!headerElement) {
				return false;
			}

			obj._headerHoehe = headerElement.offsetHeight;

			return true;
		}

		getPosition(element) {
			return this.getPositionTop(element);
		}

		/**
		 * Liefert die Position des oberen Randes des Elements.
		 * @param  DOMElement element
		 * @return number
		 */
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
		 * Prüft, ob ein übergeber Link zur aktuellen Seite führt.
		 */
		isLokalerLink(link) {
			if (link.charAt(0) == "#") return true;

			var geteilterLink = link.split("#");
			if (geteilterLink.length > 0) {
				var linkAdresse = geteilterLink[0];

				if (window.location.pathname == linkAdresse) return true;
				if (
					linkAdresse.charAt(linkAdresse.length - 1) == "/" &&
					linkAdresse.length > 1
				)
					linkAdresse = linkAdresse.substring(0, linkAdresse.length - 1);
				if (linkAdresse.length === 0) return true;

				var aktuelleAdresse = window.location.href;
				aktuelleAdresse = aktuelleAdresse.split("#");
				if (aktuelleAdresse.length < 1) return false;
				aktuelleAdresse = aktuelleAdresse[0];
				if (aktuelleAdresse.charAt(aktuelleAdresse.length - 1) == "/")
					aktuelleAdresse = aktuelleAdresse.substring(
						0,
						aktuelleAdresse.length - 1
					);
				if (linkAdresse == aktuelleAdresse) return true;

				var hrefOhneProtokoll = aktuelleAdresse.split("://");
				if (
					hrefOhneProtokoll.length >= 2 &&
					linkAdresse == hrefOhneProtokoll[1]
				)
					return true;
			}

			return false;
		}

		/**
		 * Initialisiert die Listener, die Klicks auf Hash-Links auswerten und den Reload (wenn nötig) verhindern.
		 */
		aktiviereHashListener() {
			document.addEventListener("click", this.hashListener.bind(this));
		}
		deaktiviereHashListener() {
			document.removeEventListener("click", this.hashListener.bind(this));
		}

		getHashFromLink(linkelement) {
			let obj = this;
			let hashWert = "";
			if (typeof linkelement === "string") {
				if (!isLokalerLink(linkelement)) {
					throw "Es handelt sich um keinen lokalen Link.";
				}
				var geteilterLink = link.split("#");
				if (geteilterLink.length < 2) {
					throw "Im Link-String wurde kein Hash-Wert gefunden.";
				}
				hashWert = geteilterLink[geteilterLink.length - 1];
			} else if (linkelement instanceof Element) {
				if (
					!linkelement ||
					!matches(
						linkelement,
						'a[href*="#"]:not([href="#"]):not([data-toggle])'
					)
				) {
					throw "Das übergebene Element ist kein auswertbares Link-Element.";
				}

				if (!obj.isLokalerLink(linkelement.getAttribute("href"))) {
					throw "Es handelt sich um keinen lokalen Link.";
				}

				if (linkelement.hash.includes("=") || linkelement.hash.includes("&")) {
					throw "Der ermittelte Hash-Wert ist nicht valide.";
				}

				hashWert = linkelement.hash;
			} else {
				throw "Es wurde kein valides Link-Element übergeben.";
			}

			return hashWert;
		}

		/**
		 * Liefert zu einem übergebenen Link(-String) oder einem href-Element das Zielelement, zu dem gescrollt würde.
		 * @param  string|Element linkelement
		 * @return Element
		 */
		getZielElement(linkelement) {
			let obj = this;
			let hashWert = obj.getHashFromLink(linkelement);

			// Sucht das Zielelement anhand der ID:
			const zielElement = document.querySelector(hashWert);
			if (!(zielElement instanceof Element)) {
				throw "Es wurde kein valides Ziel-Element gefunden.";
			}
			return zielElement;
		}

		hashListener(event) {
			const obj = this;

			if (!event.target) {
				return true;
			}

			let element = event.target;
			if (!matches(element, "a")) {
				element = element.closest("a");
			}

			if (
				!element ||
				!matches(element, 'a[href*="#"]:not([href="#"]):not([data-toggle])')
			) {
				return true;
			}

			let zielElement = false;
			try {
				zielElement = obj.getZielElement(element);
			} catch (err) {
				return true;
			}
			if (!(zielElement instanceof Element)) {
				return true;
			}

			event.preventDefault();
			obj.hinscrollen(zielElement);

			return false;
		}

		/**
		 * Scrollt zu einem Element. Optional können scrollOffset, animationsDatuer oder animationsTyp angegeben werden.
		 * @param  DOMElement  element
		 * @param  object  options
		 */
		hinscrollen(element, options) {
			const obj = this;
			if (typeof options !== "object") {
				options = {};
			}

			if (element.length < 1) {
				return false;
			}

			let animeOptions = {
				targets: "html, body",
				scrollTop: function(el) {
					return scrollPosition;
				},
				easing: [0.42, 0.1, 0.3, 1],
				duration: function(el, i, l) {
					return 1000 + i * 1000;
				},
			};

			// Die Parameter für die Anime-Funktion können überschrieben werden:
			for (optionkey in options) {
				animeOptions[optionkey] = options[optionkey];
			}

			// Bereits laufende Animationen stoppen:
			anime.remove("html, body");

			let scrollPosition = obj.getPositionTop(element) - obj.scrollOffset;

			// Scrollen stoppen, wenn der User scrollt:
			const scrollEvents = [
				"mousedown",
				"wheel",
				"DOMMouseScroll",
				"mousewheel",
				"keyup",
				"touchmove",
			];
			const scrollEventHandler = function(evt) {
				anime.remove("html, body");
				removeScrollEventHandler();
			};
			const removeScrollEventHandler = function() {
				for (let evtname of scrollEvents) {
					window.removeEventListener(evtname, scrollEventHandler);
				}
			};
			for (let evtname of scrollEvents) {
				window.addEventListener(evtname, scrollEventHandler, false);
			}

			var finishedPromise = anime(animeOptions).finished.then(function() {
				removeScrollEventHandler();
			});

			finishedPromise.update = function(anim) {
				if (!anim.completed) {
					// Die Scroll-Potition muss ständig neu berechnet werden, falls sich die Position des Zielelements ändert:
					scrollPosition = obj.getPositionTop(element) - obj.scrollOffset;
				}
			};
		}

		get navigationHighlighting() {
			return this._navigationHighlighting;
		}
		set navigationHighlighting(wert) {
			this._navigationHighlighting = !!wert;
			if (this._navigationHighlighting) {
				// Navigations-Highlighting aktivieren, Menü-Link-Elemente neu einlesen:
				if (
					typeof this.highlightSelektor === "string" &&
					this.highlightSelektor.length > 0
				) {
					this.ueberwacheSelektor(this.highlightSelektor);
				}
				this.aktiviereHighlightListener();
			} else {
				this.deaktiviereHighlightListener();
			}
		}

		/**
		 * Initialisiert die Listener, die Klicks auf Hash-Links auswerten und den Reload (wenn nötig) verhindern.
		 */
		aktiviereHighlightListener() {
			const obj = this;
			document.removeEventListener("scroll", obj.positionPruefen.bind(obj));

			// document.addEventListener('click', this.hashListener.bind(this));
			if (typeof throttle === "function") {
				// wenn lodash verfügbar ist, wird die throttle-Funktion genutzt (spart Rechenleistung)
				document.addEventListener(
					"scroll",
					throttle(obj.positionPruefen.bind(obj), 100)
				);
			} else {
				document.addEventListener("scroll", obj.positionPruefen.bind(obj));
			}
		}
		deaktiviereHighlightListener() {
			document.removeEventListener("scroll", obj.positionPruefen.bind(obj));
		}

		/**
		 * Überwache einen übergebenen Selektor. Mögliche Options sind:
		 * 	- sektion: Das Sektionselement, das den Link aktivieren soll, wenn es in den Viewport kommt. Wenn nicht gesetzt, wird der Link nach einem Hash-Wert durchsucht.
		 * 	- aktivKlasse: Die Klasse, die dem Link angehängt wird, wenn er aktiviert wird.
		 * 	- aktiviereParent: Soll stattdessen das Parent-Element die Aktiv-Klasse bekommen?
		 * 	- aktiviereParentSelektor: Hier kann ein Selektor für das Parent-Element angegeben werden, das die Aktiv-Klasse erhalten soll.
		 *
		 * @param  String selektorString
		 * @param  object options
		 */
		ueberwacheSelektor(selektorString, options) {
			const obj = this;
			const elemente = document.querySelectorAll(selektorString);
			for (let index in elemente) {
				const element = elemente[index];

				if (typeof element !== "object" || !(element instanceof Element)) {
					continue;
				}

				try {
					obj.ueberwacheLink(element, options);
				} catch (err) {
					// console.log(err, selektorString, typeof element, element);
				}
			}

			return true;
		}

		/**
		 * Überwache das übergebene Link-Element. Mögliche Options sind:
		 * 	- sektion: Das Sektionselement, das den Link aktivieren soll, wenn es in den Viewport kommt. Wenn nicht gesetzt, wird der Link nach einem Hash-Wert durchsucht.
		 * 	- aktivKlasse: Die Klasse, die dem Link angehängt wird, wenn er aktiviert wird.
		 * 	- aktiviereParent: Soll stattdessen das Parent-Element die Aktiv-Klasse bekommen?
		 * 	- aktiviereParentSelektor: Hier kann ein Selektor für das Parent-Element angegeben werden, das die Aktiv-Klasse erhalten soll.
		 *
		 * @param  DOMElement linkelement
		 * @param  object options
		 */
		ueberwacheLink(linkelement, options) {
			const obj = this;
			if (!(linkelement instanceof Element)) {
				throw "Es wurde kein valides Link-Element übergeben.";
			}
			if (typeof options !== "object") {
				options = {};
			}

			let sektionsElement = obj.getZielElement(linkelement);
			if (options.sektion instanceof Element) {
				sektionsElement = options.sektion;
			} else if (typeof options.sektion === "string") {
				sektionsElement = document.querySelector("#" + options.sektion);
			}

			if (!(sektionsElement instanceof Element)) {
				throw "Es wurde kein Sektions-Element gefunden.";
			}

			let hashWert = sektionsElement.getAttribute("id");
			if (typeof options.hashWert === "string" && options.hashWert.length > 0) {
				hashWert = options.hashWert;
			}

			if (typeof hashWert !== "string" || hashWert.length < 1) {
				throw "Es konnte kein Hash-Wert ermittelt werden.";
			}

			let aktivKlasse = "active";
			if (
				typeof options.aktivKlasse === "string" &&
				options.aktivKlasse.length > 0
			) {
				aktivKlasse = options.aktivKlasse;
			} else if (
				typeof obj.aktivKlasse === "string" &&
				obj.aktivKlasse.length > 0
			) {
				aktivKlasse = obj.aktivKlasse;
			}

			let aktiviereParent = false;
			if (options.aktiviereParent !== undefined) {
				aktiviereParent = !!options.aktiviereParent;
			} else if (obj.aktiviereParent !== undefined) {
				aktiviereParent = !!obj.aktiviereParent;
			}

			let aktiviereParentSelektor = "";
			if (
				typeof options.aktiviereParentSelektor === "string" &&
				options.aktiviereParentSelektor.length > 0
			) {
				aktiviereParentSelektor = options.aktiviereParentSelektor;
			} else if (
				typeof obj.aktiviereParentSelektor === "string" &&
				obj.aktiviereParentSelektor.length > 0
			) {
				aktiviereParentSelektor = obj.aktiviereParentSelektor;
			}

			if (typeof obj.sektionen[hashWert] === "object") {
				// Die Sektion existiert schon. Es muss also nur der Navlink hinzugefügt werden.

				// Wenn das Linkelement schon existiert, wird es zuerst gelöscht.
				remove(obj.sektionen[hashWert], function(n) {
					if (typeof n !== "object") return true;
					if (n.element === linkelement) return true;
					return false;
				});

				obj.sektionen[hashWert].navLinks.push({
					element: linkelement,
					aktivKlasse: aktivKlasse,
					aktiviereParent: aktiviereParent,
					aktiviereParentSelektor: aktiviereParentSelektor,
				});
			} else {
				obj.sektionen[hashWert] = {
					// Das Element, bei dem die Menüpunkte aktiv werden sollen, sobald es in den Viewport kommt:
					element: sektionsElement,

					// Die Menüpunkte:
					navLinks: [
						{
							element: linkelement,
							aktivKlasse: aktivKlasse,
							aktiviereParent: aktiviereParent,
							aktiviereParentSelektor: aktiviereParentSelektor,
						},
					],

					// Der Hash-Wert (ID) des Elements:
					hashWert: hashWert,
				};
			}

			return true;
		}

		get sektionen() {
			if (
				this._sektionen === undefined ||
				typeof this._sektionen !== "object"
			) {
				this.sektionen = {};
			}
			return this._sektionen;
		}

		set sektionen(wert) {
			if (typeof wert !== "object") {
				return false;
			}
			this._sektionen = wert;
			return true;
		}

		positionPruefen() {
			const obj = this;
			const aktuellePosition =
				window.pageYOffset || document.documentElement.scrollTop;

			let etwasGefunden = false;
			// Pro Sektion wird geprüft, ob diese gerade aufgrund der Scrollposition aktiv ist:
			for (const hashWert in obj.sektionen) {
				const sektion = obj.sektionen[hashWert];

				if (
					aktuellePosition >=
						obj.getPositionTop(sektion.element) - obj.scrollOffset - 1 &&
					aktuellePosition <=
						obj.getPositionBottom(sektion.element) - obj.scrollOffset
				) {
					obj.aktiviereSektion(sektion);
					etwasGefunden = true;
					break;
				}
			}

			if (!etwasGefunden) {
				obj.deaktiviereAlleSektionen(true);
			}
		}

		/**
		 * Aktiviert den Hash-Wert und highlightet verknüpfte Menüelemente
		 * @param  string  hashWert
		 */
		set aktiverHash(hashWert) {
			if (this._aktiverHash != hashWert) {
				if (history.pushState) {
					if (typeof hashWert === "string" && hashWert.length > 0) {
						history.pushState(
							null,
							document.title,
							window.location.pathname + "#" + hashWert + window.location.search
						);
					} else {
						history.pushState(
							"",
							document.title,
							window.location.pathname + window.location.search
						);
					}
				} else {
					document.location.hash = "#" + hashWert;
				}
				this._aktiverHash = hashWert;
			}
		}

		get aktiverHash() {
			return this._aktiverHash;
		}

		aktiviereSektion(sektion) {
			const obj = this;
			if (sektion.hashWert === obj.aktiverHash) {
				return true;
			}

			obj.deaktiviereAlleSektionen(false);
			obj.aktiverHash = sektion.hashWert;

			for (const index in sektion.navLinks) {
				const navLink = sektion.navLinks[index];

				let elementZumAktivieren = navLink.element;
				if (navLink.aktiviereParent) {
					// Ein Parent-Element soll mit der Aktiv-Klasse versehen werden.
					elementZumAktivieren = navLink.element.parentNode;
					if (
						typeof navLink.aktiviereParentSelector === "string" &&
						navLink.aktiviereParentSelector.length > 0
					) {
						elementZumAktivieren = navLink.element.closest(
							navLink.aktiviereParentSelector
						);
					}
				}
				addClass(elementZumAktivieren, navLink.aktivKlasse);

				// In Dropdowns: Dropdown-Parent ebenfalls als Aktiv markieren
				if (hasClass(elementZumAktivieren, "dropdown-item")) {
					addClass(
						elementZumAktivieren
							.closest(".dropdown")
							.querySelector(".nav-link.dropdown-toggle"),
						navLink.aktivKlasse
					);
				}
			}
		}

		deaktiviereAlleSektionen(hashLoeschen) {
			let obj = this;
			if (hashLoeschen) {
				obj.aktiverHash = "";
			}

			for (let hashWert in obj.sektionen) {
				let sektion = obj.sektionen[hashWert];

				for (let index in sektion.navLinks) {
					let navLink = sektion.navLinks[index];

					let elementZumDeaktivieren = navLink.element;
					if (navLink.aktiviereParent) {
						// Ein Parent-Element sollte mit der Aktiv-Klasse versehen werden.
						elementZumDeaktivieren = navLink.element.parentNode;
						if (
							typeof navLink.aktiviereParentSelector === "string" &&
							navLink.aktiviereParentSelector.length > 0
						) {
							elementZumDeaktivieren = navLink.element.closest(
								navLink.aktiviereParentSelector
							);
						}
					}
					removeClass(elementZumDeaktivieren, navLink.aktivKlasse);

					// In Dropdowns: Dropdown-Parent ebenfalls als Aktiv entfernen
					if (hasClass(elementZumDeaktivieren, "dropdown-item")) {
						removeClass(
							elementZumDeaktivieren
								.closest(".dropdown")
								.querySelector(".nav-link.dropdown-toggle"),
							navLink.aktivKlasse
						);
					}
				}
			}
		}
	}

	return Scrollinator;
};
export default s();
