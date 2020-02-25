import { merge, clone } from "lodash-es";

class AjaxCall {
	constructor(options) {
		if (typeof options !== 'object') {
			options = {};
		}

		this._url = "";
		this._path = "";
		this._method = "GET";
		this._headers = {
			"Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
			"X_REQUESTED_WITH": "XMLHttpRequest",
			"x-requested-with": "XMLHttpRequest",
			"HTTP_X_REQUESTED_WITH": "XMLHttpRequest"
		};
		this._getParams = {};
		this._postParams = {};

		if (options.initialiseWithCurrentLocation !== false) {
			this._url = window.location.origin;
			this._path = window.location.pathname;
			this.importGet();
		}

		if (options.url !== undefined) {
			this.url = options.url;
		}
		if (options.path !== undefined) {
			this.path = options.path;
		}
		if (options.method !== undefined) {
			this.method = options.method;
		}
		if (options.getParams !== undefined) {
			this.addGetParams(options.getParams);
		}
		if (options.postParams !== undefined) {
			this.addPostParams(options.postParams);
		}
		if (options.headers !== undefined) {
			this.addHeaders(options.headers);
		}
	}

	clone() {
		let klon = new AjaxCall();
		klon.url = this.url;
		klon.path = this.path;
		klon.method = this.method;
		klon.getParams = clone(this.getParams);
		klon.postParams = clone(this.postParams);
		klon.headers = clone(this.headers);
		return klon;
	}

	get url() {
		return this._url;
	}

	set url(value) {
		if (!value) value = '';
		else if (typeof value !== 'string') {
			value = '' + value;
		}
		this._url = value;
	}

	get path() {
		return this._path;
	}

	set path(value) {
		if (!value) value = '';
		else if (typeof value !== 'string') {
			value = '' + value;
		}
		this._path = value;
	}

	get method() {
		return this._method;
	}

	set method(value) {
		if (!value) value = '';
		else if (typeof value !== 'string') {
			value = '' + value;
		}
		this._method = value;
	}

	get getParams() {
		return this._getParams;
	}

	/**
	 * Legt die Daten des Objekts fest
	 * @param  string|object
	 * @return object
	 */
	set getParams(value) {
		if (typeof value === 'string') {
			this.importGet(value);
		} else if (typeof value === 'object') {
			this._getParams = value;
		} else {
			this.importGet(false);
		}
	}

	setGetParam(key, value) {
		this._getParams[key] = value;
		return this._getParams;
	}

	removeGetParam(key) {
		if (this._getParams[key] !== undefined) {
			delete this._getParams[key];
		}
		return this._getParams;
	}

	addGetParams(params) {
		merge(this._getParams, params);
	}

	/*
	* Importiert einen URL-String und liest daraus die GET-Parameter ein.
	* https://www.sitepoint.com/get-url-parameters-with-javascript/
	*
	* @param string url (optional)
	*/
	importGet(url) {
		// get query string from url (optional) or window
		let queryString = url ? url.split('?')[1] : window.location.search.slice(1);
		queryString = decodeURIComponent(queryString);

		// we'll store the parameters here
		let obj = {};

		// if query string exists
		if (queryString) {

			// stuff after # is not part of query string, so get rid of it
			queryString = queryString.split('#')[0];

			// split our query string into its component parts
			let arr = queryString.split('&');

			for (const arrayelement of arr) {
				// separate the keys and the values
				const a = arrayelement.split('=');

				// in case params look like: list[]=thing1&list[]=thing2
				let paramNum;
				let paramName = a[0].replace(/\[\d*\]/, (v) => {
					paramNum = v.slice(1, -1);
					return '';
				});

				if (typeof paramName !== 'string') {
					continue;
				}

				// set parameter value (use 'true' if empty)
				let paramValue = typeof (a[1]) === 'undefined' ? true : a[1];

				if (typeof paramValue !== 'string') {
					continue;
				}

				// (optional) keep case consistent
				paramName = paramName.toLowerCase();
				paramValue = paramValue.toLowerCase();

				// if parameter name already exists
				if (obj[paramName]) {
					// convert value to array (if still string)
					if (typeof obj[paramName] !== 'object' || !Array.isArray(obj[paramName])) {
						obj[paramName] = [obj[paramName]];
					}
					// if no array index number specified...
					if (typeof paramNum === 'undefined' || (typeof paramNum === 'string' && paramNum.length <= 0)) {
						// put the value on the end of the array
						obj[paramName].push(paramValue);
					} else {
						// if array index number specified...
						// put the value at that index number
						obj[paramName][paramNum] = paramValue;
					}
				} else {
					// if param name doesn't exist yet, set it
					obj[paramName] = paramValue;
				}

			}
		}

		this._getParams = obj;
	}

	/**
	 * Liefert alle GET-Params als String
	 * @return string
	 */
	exportGet() {
		return this.objectToQueryString(this.getParams);
	}

	toBrowserUrl(replace = true) {
		// Adressleiste umschreiben:
		if (replace) {
			window.history.replaceState(
				{},
				document.title,
				"?" + this.exportGet() + (window.location.hash ? window.location.hash : '')
			);
		} else {
			window.history.pushState(
				{},
				document.title,
				"?" + this.exportGet() + (window.location.hash ? window.location.hash : '')
			);
		}
	}

	get postParams() {
		return this._postParams;
	}

	set postParams(value) {
		if (typeof value === 'object') {
			this._postParams = value;
		}
	}

	setPostParam(key, value) {
		this._postParams[key] = value;
		return this._postParams;
	}

	removePostParam(key) {
		if (this._postParams[key] !== undefined) {
			delete this._postParams[key];
		}
		return this._postParams;
	}

	addPostParams(params) {
		merge(this._postParams, params);
	}

	/**
	 * Liefert alle POST-Params als String
	 * @return string
	 */
	exportPost() {
		return this.objectToQueryString(this.postParams);
	}

	/**
	 * Wandelt ein Objekt in einen Query-String um
	 * @param  object a
	 * @return string
	 */
	objectToQueryString(a) {
		let prefix, add, r20, output;
		let s = [];
		r20 = /%20/g;
		add = function (key, value) {
			// If value is a function, invoke it and return its value
			value = (typeof value == 'function') ? value() : (value == null ? "" : value);
			s[s.length] = encodeURIComponent(key) + "=" + encodeURIComponent(value);
		};
		if (a instanceof Array) {
			a.forEach((value, key) => add(key, value));
		} else {
			for (prefix in a) {
				this.buildParams(prefix, a[prefix], add);
			}
		}
		output = s.join("&").replace(r20, "+");
		return output;
	}

	buildParams(prefix, obj, add) {
		let name, i, l;
		let rbracket = /\[\]$/;
		if (obj instanceof Array) {
			for (i = 0, l = obj.length; i < l; i++) {
				if (rbracket.test(prefix)) {
					add(prefix, obj[i]);
				} else {
					this.buildParams(prefix + "[" + (typeof obj[i] === "object" ? i : "") + "]", obj[i], add);
				}
			}
		} else if (typeof obj == "object") {
			// Serialize object item.
			for (name in obj) {
				this.buildParams(prefix + "[" + name + "]", obj[name], add);
			}
		} else {
			// Serialize scalar item.
			add(prefix, obj);
		}
	}

	get headers() {
		return this._headers;
	}

	set headers(value) {
		if (typeof value === 'object') {
			this._headers = value;
		}
	}

	setHeader(key, value) {
		this._headers[key] = value;
		return this._headers;
	}

	removeHeader(key) {
		if (this._headers[key] !== undefined) {
			delete this._headers[key];
		}
		return this._headers;
	}

	addHeaders(params) {
		merge(this._headers, params);
		return this._headers;
	}

	/**
	 * Liefert die volle Anfrage-URL
	 *
	 * @param  boolean includeGetParams  Sollen die GET-Parameter mit angehängt werden? (Default: true)
	 * @return string url
	 */
	getUrl(includeGetParams) {
		let output = this.url + (!this.url.endsWith('/') && !this.path.startsWith('/') ? '/' : '') + this.path;
		let getString = this.exportGet();
		if ((includeGetParams || includeGetParams === undefined) && typeof getString === 'string' && getString.length > 0) {
			output = output + '?' + getString;
		}
		return output;
	}

	abort() {
		if (this._controller === undefined) {
			return false;
		}
		this._controller.abort();
		return true;
	}

	fetch(options) {
		let opts = typeof options === 'object' ? options : {};

		let fetchOptions = {
			method: this.method,
			headers: new Headers(this.headers),
			credentials: "same-origin",
			body: this.exportPost()
		};

		this.abort();

		// Feature detect
		if ("AbortController" in window && opts.abortable) {
			this._controller = new AbortController();
			fetchOptions.signal = this._controller.signal;
		}
		if(opts.abortable === undefined){
			delete opts.abortable;
		}

		for (const key in opts) {
			if (key === "body" && typeof opts[key] === 'object') {
				fetchOptions.body = this.objectToQueryString(opts[key]);
			} else {
				fetchOptions[key] = opts[key];
			}
		}

		if (fetchOptions.method.toUpperCase() === 'GET') {
			// GET-Anfragen dürfen keinen Body haben!
			delete fetchOptions.body;
		}

		return fetch(this.getUrl(), fetchOptions);
	}

	fetchJSON(options) {
		const obj = this;
		return new Promise(function (resolve, reject) {
			obj.fetch(options)
				.then((response) => {
					// console.log(typeof response, response);

					if (response.status === 204) {
						resolve({});
						return;
					}

					if (!response.ok) {
						throw {
							code: response.status,
							response: response
						};
					}

					return response.json();
				})
				.then((response) => {
					// console.log("Ergebnis:", response);
					if (typeof response !== 'object') {
						throw {
							code: 500,
							message: "Keine valide Antwort vom Server erhalten."
						};
					}
					resolve(response);
				}).catch((error) => {
					let code = 500;
					let message = 'Ein unbekannter Fehler ist aufgetreten.';
					const mainErrordetails = obj._extractErrorInfo(error);
					if (mainErrordetails.code) {
						code = mainErrordetails.code;
					}
					if (mainErrordetails.message) {
						message = mainErrordetails.message;
					}

					if (typeof error.response === 'object') {
						try {
							error.response.json().then((json) => {
								const errordetails = obj._extractErrorInfo(json);
								if (errordetails.code) {
									code = errordetails.code;
								}
								if (errordetails.message) {
									message = errordetails.message;
								}
								json.code = code;
								json.message = message;
								if (json.error !== undefined) {
									delete json.error;
								}

								reject(json);
							}).catch((e) => {
								error.response.text().then((text) => {
									const errordetails = obj._extractErrorInfo(text);
									if (errordetails.code) {
										code = errordetails.code;
									}
									if (errordetails.message) {
										message = errordetails.message;
									}
									reject({
										code: code,
										message: text
									});
								}).catch((e) => {
									reject({
										code: code,
										message: message
									});
								});
							});
						} catch (e) {
							reject({
								code: code,
								message: message
							});
						}
					} else {
						reject({
							code: code,
							message: message
						});
					}
				});
		});
	}
	_extractErrorInfo(error) {
		let code = false;
		let message = false;

		if (typeof error === 'string') {
			message = error;
		} else if (typeof error === 'object') {
			if (error.code) {
				code = error.code;
			} else if (error.status) {
				code = error.status;
			} else if (typeof error.error === 'object' && error.error.code) {
				code = error.error.code;
			}

			if (error.message) {
				message = error.message;
			} else if (typeof error.error === 'string') {
				message = error.error;
			} else if (typeof error.error === 'object' && typeof error.error.message === 'string') {
				message = error.error.message;
			}
		}

		return {
			code: code,
			message: message
		};
	}
}

export default AjaxCall;