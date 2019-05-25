import { ready, hasClass, addClass, removeClass, createElementFromHTML, removeElements, trigger, nextUntil } from './classes/hilfsfunktionen.js';
import AjaxAnfrage from './classes/AjaxAnfrage.js';
import { debounce, uniq, remove } from 'lodash';

(async () => {
	const elemente = document.querySelectorAll('.aktuelles-kacheln');
	if (elemente.length > 0) {
		const msnr = await import('./masonry.js');
		const imagesloadedLoad = await import('imagesloaded');
		const ImagesLoaded = imagesloadedLoad.default;
		const msryLoad = await import(/* webpackChunkName: "masonry-import" */ 'masonry-layout');
		const Masonry = msryLoad.default;
		const progressively = await import(/* webpackChunkName: "bildtools" */ 'progressively');

		const logging = false;

		/**
		* Schickt die aktuellen Filtereinstellungen ab
		
		*/
		function filterAbschicken(kachelElement, grid, ajaxAnfrage) {
			ajaxAnfrage = ajaxAnfrage.clone();
			let getParams = ajaxAnfrage.getParams;
			let historyParams = Object.assign({}, getParams);

			delete historyParams.start;
			delete historyParams.offset;

			// Adressleiste umschreiben:
			window.history.pushState(
				{},
				document.title,
				'?' + ajaxAnfrage.objectToQueryString(historyParams)
			);

			let vorhandeneIDs = [];
			const cards = grid.querySelectorAll('.beitrag-card');
			for (let index in cards) {
				const card = cards[index];
				if (typeof card !== 'object' || !(card instanceof Element)) {
					continue;
				}

				let dataID = card.getAttribute("data-id");
				if (typeof dataID === 'string' && dataID.length > 0) {
					vorhandeneIDs.push(dataID);
				}
			}

			getParams['twack-ajax'] = 1;
			getParams.action = 'getComponent',
				getParams.component = "AktuellesService";
			getParams.type = "service";
			getParams.htmlAusgabe = true;
			getParams.vorhandeneIDs = vorhandeneIDs;
			ajaxAnfrage.getParams = getParams;

			ajaxAnfrage.fetch({ method: 'GET' })
				.then(function (response) {
					let json = response.json();
					if (response.status >= 200 && response.status < 300) {
						return json;
					}
					return json.then(Promise.reject.bind(Promise));
				})
				.then(function (data) {
					if (logging) {
						console.log("Anfrage erfolgreich: ", data);
					}

					if (typeof data.beitraege === 'object' && Array.isArray(data.beitraege)) {
						if (getParams.offset !== undefined || getParams.start !== undefined) {
							// Ein Offset ist gesetzt. Die Ergebnisse sollen deshalb nur hinzugefügt werden.
							beitragKachelnAnzeigen(kachelElement, grid, data.beitraege, false);
						} else {
							// Vergleicht die vorhandenen Kacheln, und fügt noch nicht vorhandene Elemente an der passenden Stelle ein:
							beitragKachelnAnzeigen(kachelElement, grid, data.beitraege, true);
						}
					}

					const container = grid.closest('.aktuelles-inhalte');
					if (typeof container === 'object' && container instanceof Element) {
						const filtereinstellungen = container.querySelector('.filtereinstellungen');
						if (typeof filtereinstellungen === 'object' && filtereinstellungen instanceof Element) {

							// Schlagwort-Aktiv setzen
							const aktiveSchlagwoerter = filtereinstellungen.querySelectorAll('.schlagwoerter .wort');
							for (let i in aktiveSchlagwoerter) {
								const s = aktiveSchlagwoerter[i];
								if (typeof s !== 'object' || !(s instanceof Element)) {
									continue;
								}
								removeClass(s, 'aktiv');
							}

							if (typeof getParams === 'object' && getParams.schlagwoerter !== undefined) {
								if (typeof getParams.schlagwoerter === 'string' && getParams.schlagwoerter.length > 0) {
									addClass(filtereinstellungen.querySelector('.schlagwoerter .wort[data-id="' + getParams.schlagwoerter + '"]'), 'aktiv');
								} else if (typeof getParams.schlagwoerter === 'object' && Array.isArray(getParams.schlagwoerter)) {
									for (let i in getParams.schlagwoerter) {
										const schlagwortID = getParams.schlagwoerter[i];
										addClass(filtereinstellungen.querySelector('.schlagwoerter .wort[data-id="' + schlagwortID + '"]'), 'aktiv');
									}
								}
							}
						}
					}

					var gesamtanzahlElemente = kachelElement.querySelectorAll('.gesamtanzahl');
					if (gesamtanzahlElemente.length < 1) {
						grid.insertAdjacentHTML('beforebegin', '<i class="gesamtanzahl"></i>');
						gesamtanzahlElemente = kachelElement.querySelectorAll('.gesamtanzahl');
					}
					if (data.gesamtAnzahl !== undefined && data.gesamtAnzahl > 0) {
						for (let i in gesamtanzahlElemente) {
							const gesamtanzahlElement = gesamtanzahlElemente[i];
							if (typeof gesamtanzahlElement !== 'object' || !(gesamtanzahlElement instanceof Element)) {
								continue;
							}
							gesamtanzahlElement.textContent = 'insgesamt ' + data.gesamtAnzahl + ' Beiträge';
							gesamtanzahlElement.style.display = 'block';
						}
					} else {
						for (let i in gesamtanzahlElemente) {
							const gesamtanzahlElement = gesamtanzahlElemente[i];
							if (typeof gesamtanzahlElement !== 'object' || !(gesamtanzahlElement instanceof Element)) {
								continue;
							}
							gesamtanzahlElement.textContent = 'keine Beiträge';
							gesamtanzahlElement.style.display = 'none';
						}
					}

					let buttonGruppe = kachelElement.querySelector('.btn-group');
					if (typeof buttonGruppe !== 'object' || !(buttonGruppe instanceof Element)) {
						grid.insertAdjacentHTML('beforebegin', '<div class="btn-group" role="group"></div>');
						buttonGruppe = kachelElement.querySelector('.btn-group');
					}

					let mehrButton = buttonGruppe.querySelector('[data-aktion="weitere_laden"]');
					if (typeof mehrButton !== 'object' || !(mehrButton instanceof Element)) {
						buttonGruppe.insertAdjacentHTML('beforeend', '<button class="btn btn-secondary" data-aktion="weitere_laden" type="button">Weitere laden...</button>');
						mehrButton = buttonGruppe.querySelector('[data-aktion="weitere_laden"]');
					}
					if (data.hatMehr === true) {
						let offset = grid.querySelectorAll('.masonry-grid-item').length;
						if (data.letztesElementIndex && parseInt(data.letztesElementIndex) !== NaN) {
							offset = parseInt(data.letztesElementIndex) + 1;
						}

						mehrButton.setAttribute('data-offset', offset);
						mehrButton.style.display = '';
					} else {
						mehrButton.style.display = 'none';
					}
				}, function (response) {
					if (logging) {
						console.error('Fetch Error :-S', response);
					}

				}).catch(function (err) {
					if (logging) {
						console.error('Fetch Error Catch :-S', err);
					}
				});
		}

		function beitragKachelnAnzeigen(kachelElement, grid, beitragArray, abgleichen) {
			if (typeof grid !== 'object' || !(grid instanceof Element)) {
				return false;
			}

			if (typeof beitragArray !== 'object' || !Array.isArray(beitragArray)) {
				return false;
			}

			if (logging) {
				console.log("Beiträge anzeigen: ", beitragArray);
			}

			let gridItems = grid.querySelectorAll('.masonry-grid-item');
			if (beitragArray.length < 1 && (abgleichen !== false || gridItems.length < 1)) {
				// Alle Elemente aus dem Grid löschen:
				removeElements(gridItems);

				grid.masonry('reloadItems').masonry('layout');
				progressively.drop();
				progressively.init({
					onLoad: function (elem) {
						trigger(elem, "bildGeladen");
					}
				});

				// Meldung anzeigen:
				const keineErgebnisseElemente = kachelElement.querySelectorAll('.keine-ergebnisse');
				if (keineErgebnisseElemente.length < 1) {
					grid.insertAdjacentHTML('afterend', '<div class="alert alert-info keine-ergebnisse" role="alert"><strong>Keine Beiträge gefunden.</strong><br/>Erweitern Sie die Filtereinstellungen, um mehr Ergebnisse zu erhalten.</div>');
				}

				trigger(grid, 'elementeGeaendert');
				return true;
			}

			removeElements(kachelElement.querySelectorAll('.keine-ergebnisse'));

			// Über die vorhandenen Elemente wird iteriert. Wenn ein Element bereits vorhanden ist, muss es nicht gelöscht werden.
			let elementIterator = grid.querySelectorAll('.masonry-grid-item')[0];

			if (abgleichen === false) {
				if (beitragArray.length < 1) {
					elementIterator = undefined;
				} else {
					// Wenn erstes Element schon vorhanden ist: Als Iterator setzen
					const fund = grid.querySelector('.beitrag-card[data-id="' + beitragArray[0].id + '"]');
					if (typeof fund === 'object' && fund instanceof Element) {
						// Das erste Element der neuen Kacheln existiert schon.
						elementIterator = fund.parentNode;
					} else {
						elementIterator = undefined;
						// const gridElemente = grid.querySelectorAll('.masonry-grid-item');
						// elementIterator = gridElemente[gridElemente.length - 1].nextElementSibling;
					}
				}
			}

			for (const index in beitragArray) {
				trigger(grid, 'elementeGeaendert');
				const beitrag = beitragArray[index];
				if (logging) {
					console.log("Nächstes Element: ", beitrag, index);
				}

				if (typeof beitrag !== 'object' || typeof beitrag.html !== 'string' || beitrag.html.length < 1 || beitrag.id === undefined) {
					// Kein valides Element. Einfach überspringen...
					if (logging) {
						console.log("Kein valides Element. Einfach überspringen...");
					}
					continue;
				}

				if (!elementIterator || elementIterator.length < 1) {
					// Kein Iterator-Element als Referenz vorhanden. Alle weiteren Elemente können einfach angehängt werden.
					if (logging) {
						console.log("Kein Iterator-Element als Referenz vorhanden. Alle weiteren Elemente können einfach angehängt werden.");
					}
					grid.insertAdjacentHTML('beforeend', ('<div class="masonry-grid-item">' + beitrag.html + '</div>'));
					continue;
				}

				if (elementIterator.querySelector('.beitrag-card').getAttribute('data-id') + '' == beitrag.id + '') {
					// Das aktuelle Ergebnis-Element existiert. Nächster Durchlauf!
					if (logging) {
						console.log("Das aktuelle Ergebnis-Element existiert. Nächster Durchlauf!", elementIterator.querySelector('.beitrag-card').getAttribute('data-id'), beitrag.id);
					}
					elementIterator = elementIterator.nextElementSibling;
					continue;
				}

				let fund = grid.querySelector('.beitrag-card[data-id="' + beitrag.id + '"]');
				if (typeof fund === 'object' && fund instanceof Element) {
					// Element existiert, es liegen aber noch zu löschende Elemente dazwischen.
					if (logging) {
						console.log("Element existiert, es liegen aber noch zu löschende Elemente dazwischen.");
					}
					fund = fund.parentNode;

					while (elementIterator) {
						if (logging) {
							console.log("Lösch-Durchlauf.", elementIterator);
						}
						if (typeof elementIterator !== 'object' || !(elementIterator instanceof Element)) {
							break;
						}
						if ((elementIterator.querySelector('.beitrag-card').getAttribute('data-id') + "") === ("" + beitrag.id)) {
							if (logging) {
								console.log("Aktuelles Element: Bis hierhin sollte gelöscht werden.");
							}
							break;
						}
						let oldElement = elementIterator;
						elementIterator = elementIterator.nextElementSibling;
						oldElement.parentNode.removeChild(oldElement);
					}


					elementIterator = fund;
					elementIterator = elementIterator.nextElementSibling;
					continue;
				}

				// Element existiert noch nicht. Hinzufügen!
				// console.log("Element existiert noch nicht. Hinzufügen!");
				elementIterator.insertAdjacentHTML('beforebegin', ('<div class="masonry-grid-item">' + beitrag.html + '</div>'));
			}

			if (abgleichen !== false) {
				while (elementIterator) {
					if (typeof elementIterator !== 'object' || !(elementIterator instanceof Element)) {
						break;
					}
					let oldElement = elementIterator;
					elementIterator = elementIterator.nextElementSibling;
					oldElement.parentNode.removeChild(oldElement);
				}
			}

			setTimeout(function () {
				trigger(grid, 'elementeGeaendert');
			}, 100);

			return true;
		}

		ready(function () {
			for (let index in elemente) {
				const kachelElement = elemente[index];
				if (typeof kachelElement !== 'object' || !(kachelElement instanceof Element)) {
					continue;
				}

				const grid = kachelElement.querySelector('.masonry-grid');
				if (typeof grid !== 'object' || !(grid instanceof Element)) {
					continue;
				}

				let msnry = Masonry.data(grid);
				msnry.on('layoutComplete', function (event, items) {
					progressively.drop();
					progressively.init({
						onLoad: function (elem) {
							trigger(elem, "bildGeladen");
						}
					});
				});

				const ajaxAnfrage = new AjaxAnfrage();

				const mehrButton = kachelElement.querySelector('[data-aktion="weitere_laden"]');
				if (typeof mehrButton === 'object' && mehrButton instanceof Element) {

					mehrButton.addEventListener("click", debounce(function (event) {
						event.preventDefault();

						let getParameter = ajaxAnfrage.getParams;
						let offset = mehrButton.getAttribute('data-offset');
						if (typeof offset === 'string' && offset.length > 0) {
							getParameter.start = offset;
						} else {
							delete getParameter.start;
						}

						ajaxAnfrage.getParams = getParameter;
						filterAbschicken(kachelElement, grid, ajaxAnfrage);
						return true;
					}, 300));
				}

				const container = grid.closest('.aktuelles-inhalte');
				if (typeof container === 'object' && container instanceof Element) {
					const filtereinstellungen = container.querySelector('.filtereinstellungen');
					if (typeof filtereinstellungen === 'object' && filtereinstellungen instanceof Element) {
						// Es wurden Filtereinstellungen gefunden, die berücksichtig werden müssen.

						const suchenBtn = filtereinstellungen.querySelector('.btn[name="suchen"]');
						if (typeof filtereinstellungen === 'object' && filtereinstellungen instanceof Element) {
							suchenBtn.style.display = 'none';
						}

						// Klicks auf die Schlagwörter werden abgefangen, und stattdessen wird eine Ajax-Anfrage zum Nachladen der Inhalte getriggert:
						const schlagwoerter = filtereinstellungen.querySelectorAll('.schlagwoerter .wort');
						if (schlagwoerter.length > 0) {
							for (let i in schlagwoerter) {
								const schlagwort = schlagwoerter[i];
								if (typeof schlagwort !== 'object' || !(schlagwort instanceof Element)) {
									continue;
								}
								schlagwort.addEventListener("click", function (event) {
									event.preventDefault();

									const schlagwortID = "" + schlagwort.getAttribute('data-id');
									if (schlagwortID.length < 1) {
										return false;
									}

									let getParameter = ajaxAnfrage.getParams;
									delete getParameter.start;

									if (typeof getParameter.schlagwoerter === 'string' && getParameter.schlagwoerter.length > 0) {
										getParameter.schlagwoerter = [getParameter.schlagwoerter];
									}
									if (typeof getParameter.schlagwoerter !== 'object' || !Array.isArray(getParameter.schlagwoerter)) {
										getParameter.schlagwoerter = [];
									}

									if (!hasClass(schlagwort, 'aktiv')) {
										getParameter.schlagwoerter.push(schlagwortID);
									} else {
										remove(getParameter.schlagwoerter, function (s) {
											return s == schlagwortID;
										});
									}

									getParameter.schlagwoerter = uniq(getParameter.schlagwoerter);
									if (getParameter.schlagwoerter.length <= 0) {
										// Keine Schlagworter: Schlagwoerter-Parameter löschen
										delete getParameter.schlagwoerter;
									}

									ajaxAnfrage.getParams = getParameter;
									filterAbschicken(kachelElement, grid, ajaxAnfrage);

									return false;
								});
							}
						}

						const freitextsucheElemente = filtereinstellungen.querySelectorAll('input[name="freitextsuche"]');
						if (freitextsucheElemente.length > 0) {
							for (let i in freitextsucheElemente) {
								const freitextsuche = freitextsucheElemente[i];
								if (typeof freitextsuche !== 'object' || !(freitextsuche instanceof Element)) {
									continue;
								}
								freitextsuche.addEventListener("keydown", debounce(function (event) {
									event.preventDefault();

									let getParameter = ajaxAnfrage.getParams;
									delete getParameter.start;

									let eingabe = freitextsuche.value;
									if (typeof eingabe === 'string' && eingabe.length > 0) {
										getParameter.freitextsuche = eingabe;
									} else {
										// Kein Eingabetext: Freitextsuche-Parameter löschen
										delete getParameter.freitextsuche;
									}

									ajaxAnfrage.getParams = getParameter;
									filterAbschicken(kachelElement, grid, ajaxAnfrage);
									return true;
								}, 300));

							}
						}
					}
				}
			}
		});
	}
})();