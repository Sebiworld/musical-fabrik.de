/* jshint -W024 */
import { ready, hasClass, addClass, removeClass, createElementFromHTML, removeElements, trigger, nextUntil } from './classes/helpers.js';
import AjaxCall from './classes/AjaxCall.js';
import { debounce, uniq, remove } from 'lodash-es';

(async () => {
	const elements = document.querySelectorAll('.articles_tiles');
	if (elements.length > 0) {
		const msnr = await import('./masonry.js');
		const imagesloadedLoad = await import('imagesloaded');
		const ImagesLoaded = imagesloadedLoad.default;
		const msryLoad = await import(/* webpackChunkName: "masonry-import" */ 'masonry-layout');
		const Masonry = msryLoad.default;
		const progressively = await import(/* webpackChunkName: "bildtools" */ 'progressively');

		const logging = false;

		/**
		* Sends the current filter settings
		*/
		function sendFilterRequest(tileElement, grid, ajaxCall) {
			ajaxCall = ajaxCall.clone();
			let getParams = ajaxCall.getParams;
			let historyParams = Object.assign({}, getParams);

			delete historyParams.start;
			delete historyParams.offset;
			delete historyParams.existingIds;
			delete historyParams.htmlOutput;

			// Rewrite address bar:
			window.history.pushState(
				{},
				document.title,
				'?' + ajaxCall.objectToQueryString(historyParams)
			);

			let existingIds = [];
			const cards = grid.querySelectorAll('.article_card');
			for (let index in cards) {
				const card = cards[index];
				if (typeof card !== 'object' || !(card instanceof Element)) {
					continue;
				}

				let dataID = card.getAttribute("data-id");
				if (typeof dataID === 'string' && dataID.length > 0) {
					existingIds.push(dataID);
				}
			}

			getParams.htmlOutput = true;
			getParams.existingIds = existingIds;
			ajaxCall.getParams = getParams;

			ajaxCall.fetch({ method: 'GET' })
				.then(function (response) {
					let json = response.json();
					if (response.status >= 200 && response.status < 300) {
						return json;
					}
					return json.then(Promise.reject.bind(Promise));
				})
				.then(function (data) {
					if (logging) {
						console.log("Request was successfull: ", data);
					}

					if (typeof data.articles === 'object' && Array.isArray(data.articles)) {
						if (getParams.offset !== undefined || getParams.start !== undefined) {
							// An offset is set. The results should therefore only be added.
							showArticleTiles(tileElement, grid, data.articles, false);
						} else {
							// Compares the existing tiles, and inserts non-existing elements at the appropriate location:
							showArticleTiles(tileElement, grid, data.articles, true);
						}
					}

					const container = grid.closest('.content_articles');
					if (typeof container === 'object' && container instanceof Element) {
						const filtersbox = container.querySelector('.filters_component');
						if (typeof filtersbox === 'object' && filtersbox instanceof Element) {

							// Set keyword active
							const activeTags = filtersbox.querySelectorAll('.tags_box .tag');
							for (let i in activeTags) {
								const tag = activeTags[i];
								if (typeof tag !== 'object' || !(tag instanceof Element)) {
									continue;
								}
								removeClass(tag, 'active');
							}

							if (typeof getParams === 'object' && getParams.tags !== undefined) {
								if (typeof getParams.tags === 'string' && getParams.tags.length > 0) {
									addClass(filtersbox.querySelector('.tags_box .tag[data-id="' + getParams.tags + '"]'), 'active');
								} else if (typeof getParams.tags === 'object' && Array.isArray(getParams.tags)) {
									for (let i in getParams.tags) {
										const tagId = getParams.tags[i];
										addClass(filtersbox.querySelector('.tags_box .tag[data-id="' + tagId + '"]'), 'active');
									}
								}
							}
						}
					}

					let totalNumberElements = tileElement.querySelectorAll('.total-number');
					if (totalNumberElements.length < 1) {
						grid.insertAdjacentHTML('beforebegin', '<i class="total-number"></i>');
						totalNumberElements = tileElement.querySelectorAll('.total-number');
					}
					if (data.totalNumber !== undefined && data.totalNumber > 0) {
						for (let i in totalNumberElements) {
							const totalNumberElement = totalNumberElements[i];
							if (typeof totalNumberElement !== 'object' || !(totalNumberElement instanceof Element)) {
								continue;
							}
							totalNumberElement.textContent = 'insgesamt ' + data.totalNumber + ' Beiträge';
							totalNumberElement.style.display = 'block';
						}
					} else {
						for (let i in totalNumberElements) {
							const totalNumberElement = totalNumberElements[i];
							if (typeof totalNumberElement !== 'object' || !(totalNumberElement instanceof Element)) {
								continue;
							}
							totalNumberElement.textContent = 'keine Beiträge';
							totalNumberElement.style.display = 'none';
						}
					}

					let buttongroup = tileElement.querySelector('.btn-group');
					if (typeof buttongroup !== 'object' || !(buttongroup instanceof Element)) {
						grid.insertAdjacentHTML('beforebegin', '<div class="btn-group" role="group"></div>');
						buttongroup = tileElement.querySelector('.btn-group');
					}

					let mehrButton = buttongroup.querySelector('[data-action="load-more"]');
					if (typeof mehrButton !== 'object' || !(mehrButton instanceof Element)) {
						buttongroup.insertAdjacentHTML('beforeend', '<button class="btn btn-secondary" data-action="load-more" type="button">Weitere laden...</button>');
						mehrButton = buttongroup.querySelector('[data-action="load-more"]');
					}
					if (data.moreAvailable === true) {
						let offset = grid.querySelectorAll('.masonry-grid-item').length;
						if (data.lastElementIndex && parseInt(data.lastElementIndex) !== NaN) {
							offset = parseInt(data.lastElementIndex) + 1;
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

		function showArticleTiles(tileElement, grid, articlesArray, compareElements) {
			if (typeof grid !== 'object' || !(grid instanceof Element)) {
				return false;
			}

			if (typeof articlesArray !== 'object' || !Array.isArray(articlesArray)) {
				return false;
			}

			if (logging) {
				console.log("Show articles: ", articlesArray);
			}

			let gridItems = grid.querySelectorAll('.masonry-grid-item');
			if (articlesArray.length < 1 && (compareElements !== false || gridItems.length < 1)) {
				// Delete all elements from the grid:
				removeElements(gridItems);

				grid.masonry('reloadItems').masonry('layout');
				progressively.drop();
				progressively.init({
					onLoad: function (elem) {
						trigger(elem, "img-loaded");
					}
				});

				// Display message:
				const keineErgebnisseElemente = tileElement.querySelectorAll('.no-results');
				if (keineErgebnisseElemente.length < 1) {
					grid.insertAdjacentHTML('afterend', '<div class="alert alert-info no-results" role="alert"><strong>Keine Beiträge gefunden.</strong><br/>Erweitern Sie die Filtereinstellungen, um mehr Ergebnisse zu erhalten.</div>');
				}

				trigger(grid, 'elements-changed');
				return true;
			}

			removeElements(tileElement.querySelectorAll('.no-results'));

			// The existing elements are iterated. If an element already exists, it does not have to be deleted.
			let elementIterator = grid.querySelectorAll('.masonry-grid-item')[0];

			if (compareElements === false) {
				if (articlesArray.length < 1) {
					elementIterator = undefined;
				} else {
					// If first element already exists: Set as iterator
					const articleFound = grid.querySelector('.article_card[data-id="' + articlesArray[0].id + '"]');
					if (typeof articleFound === 'object' && articleFound instanceof Element) {
						// The first element of the new tiles already exists.
						elementIterator = articleFound.parentNode;
					} else {
						elementIterator = undefined;
						// const gridElements = grid.querySelectorAll('.masonry-grid-item');
						// elementIterator = gridElements[gridElements.length - 1].nextElementSibling;
					}
				}
			}

			for (const index in articlesArray) {
				trigger(grid, 'elements-changed');
				const article = articlesArray[index];
				if (logging) {
					console.log("Next Element: ", article, index);
				}

				if (typeof article !== 'object' || typeof article.html !== 'string' || article.html.length < 1 || article.id === undefined) {
					// No valid element. Just skip it...
					if (logging) {
						console.log("No valid element. Just skip it...");
					}
					continue;
				}

				if (!elementIterator || elementIterator.length < 1) {
					// No iterator element available as reference. All other elements can simply be appended.
					if (logging) {
						console.log("No iterator element available as reference. All other elements can simply be appended.");
					}
					grid.insertAdjacentHTML('beforeend', ('<div class="masonry-grid-item">' + article.html + '</div>'));
					continue;
				}

				if (elementIterator.querySelector('.article_card').getAttribute('data-id') + '' == article.id + '') {
					// The current result element exists. Next cycle!
					if (logging) {
						console.log("The current result element exists. Next cycle!", elementIterator.querySelector('.article_card').getAttribute('data-id'), article.id);
					}
					elementIterator = elementIterator.nextElementSibling;
					continue;
				}

				let articleFound = grid.querySelector('.article_card[data-id="' + article.id + '"]');
				if (typeof articleFound === 'object' && articleFound instanceof Element) {
					// element exists, but there are still elements in between that need to be deleted.
					if (logging) {
						console.log("element exists, but there are still elements in between that need to be deleted.");
					}
					articleFound = articleFound.parentNode;

					while (elementIterator) {
						if (logging) {
							console.log("delete cycle.", elementIterator);
						}
						if (typeof elementIterator !== 'object' || !(elementIterator instanceof Element)) {
							break;
						}
						if ((elementIterator.querySelector('.article_card').getAttribute('data-id') + "") === ("" + article.id)) {
							if (logging) {
								console.log("Current element: This should be deleted.");
							}
							break;
						}
						let oldElement = elementIterator;
						elementIterator = elementIterator.nextElementSibling;
						oldElement.parentNode.removeChild(oldElement);
					}


					elementIterator = articleFound;
					elementIterator = elementIterator.nextElementSibling;
					continue;
				}

				// Element does not yet exist. Add it!
				// console.log("Element does not yet exist. Add it!");
				elementIterator.insertAdjacentHTML('beforebegin', ('<div class="masonry-grid-item">' + article.html + '</div>'));
			}

			if (compareElements !== false) {
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
				trigger(grid, 'elements-changed');
			}, 100);

			return true;
		}

		ready(function () {
			for (let index in elements) {
				const tileElement = elements[index];
				if (typeof tileElement !== 'object' || !(tileElement instanceof Element)) {
					continue;
				}

				const grid = tileElement.querySelector('.masonry-grid');
				if (typeof grid !== 'object' || !(grid instanceof Element)) {
					continue;
				}

				let msnry = Masonry.data(grid);
				msnry.on('layoutComplete', function () {
					progressively.drop();
					progressively.init({
						onLoad: function (elem) {
							trigger(elem, "img-loaded");
						}
					});
				});

				const ajaxCall = new AjaxCall({
					path: '/api/page' + window.location.pathname,
					headers: {
						'X-API-KEY': 'SEawMksSM8AAKnbAroSyU'
					}
				});
				ajaxCall.importGet();

				const moreBtn = tileElement.querySelector('[data-action="load-more"]');
				if (typeof moreBtn === 'object' && moreBtn instanceof Element) {

					moreBtn.addEventListener("click", debounce(function (event) {
						event.preventDefault();

						ajaxCall.importGet();
						let getParams = ajaxCall.getParams;
						let offset = moreBtn.getAttribute('data-offset');
						if (typeof offset === 'string' && offset.length > 0) {
							getParams.start = offset;
						} else {
							delete getParams.start;
						}

						ajaxCall.getParams = getParams;
						sendFilterRequest(tileElement, grid, ajaxCall);
						return true;
					}, 300));
				}

				const container = grid.closest('.content_articles');
				if (typeof container === 'object' && container instanceof Element) {
					const filtersbox = container.querySelector('.filters_component');
					if (typeof filtersbox === 'object' && filtersbox instanceof Element) {
						// Filter settings have been found that must be taken into account.

						const searchBtn = filtersbox.querySelector('.btn[name="search"]');
						if (typeof searchBtn === 'object' && searchBtn instanceof Element) {
							searchBtn.style.display = 'none';
						}

						// Clicks on the keywords are intercepted, and instead an Ajax request to reload the content is triggered:
						const tags = filtersbox.querySelectorAll('.tags_box .tag');
						if (tags.length > 0) {
							for (let i in tags) {
								const tag = tags[i];
								if (typeof tag !== 'object' || !(tag instanceof Element)) {
									continue;
								}
								tag.addEventListener("click", function (event) {
									event.preventDefault();

									const tagId = "" + tag.getAttribute('data-id');
									if (tagId.length < 1) {
										return false;
									}

									ajaxCall.importGet();
									let getParams = ajaxCall.getParams;
									delete getParams.start;

									if (typeof getParams.tags === 'string' && getParams.tags.length > 0) {
										getParams.tags = [getParams.tags];
									}
									if (typeof getParams.tags !== 'object' || !Array.isArray(getParams.tags)) {
										getParams.tags = [];
									}

									if (!hasClass(tag, 'active')) {
										getParams.tags.push(tagId);
									} else {
										remove(getParams.tags, function (s) {
											return s == tagId;
										});
									}

									getParams.tags = uniq(getParams.tags);
									if (getParams.tags.length <= 0) {
										// No keywords: Delete keyword parameters
										delete getParams.tags;
									}

									ajaxCall.getParams = getParams;
									sendFilterRequest(tileElement, grid, ajaxCall);

									return false;
								});
							}
						}

						const searchElements = filtersbox.querySelectorAll('input[name="q"]');
						if (searchElements.length > 0) {
							for (let i in searchElements) {
								const searchElement = searchElements[i];
								if (typeof searchElement !== 'object' || !(searchElement instanceof Element)) {
									continue;
								}
								searchElement.addEventListener("keydown", debounce(function (event) {
									event.preventDefault();

									ajaxCall.importGet();
									let getParams = ajaxCall.getParams;
									delete getParams.start;

									let input = searchElement.value;
									if (typeof input === 'string' && input.length > 0) {
										getParams.q = input;
									} else {
										// No input text: Delete user-defined text search parameters
										delete getParams.q;
									}

									ajaxCall.getParams = getParams;
									sendFilterRequest(tileElement, grid, ajaxCall);
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