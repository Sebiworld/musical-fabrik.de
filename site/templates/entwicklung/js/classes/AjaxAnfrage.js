class AjaxAnfrage {
	constructor(wert) {
		this.url = window.location.origin;
		this.pfad = window.location.pathname;

		this._getParams = {};
		this._postParams = {};
		this.getParams = false;
	}

	clone(){
		let klon = new AjaxAnfrage();
		klon.url = this.url;
		klon.pfad = this.pfad;
		klon.getParams = Object.assign({}, this.getParams);
		klon.postParams = Object.assign({}, this.postParams);
		return klon;
	}

	get getParams(){
		return this._getParams;
	}

	/**
	 * Legt die Daten des Objekts fest
	 * @param  string|object
	 * @return object
	 */
	set getParams(wert) {
		if(typeof wert === 'string'){
			this.importGet(wert);
			return true;
		}else if(typeof wert === 'object'){
			this._getParams = wert;
			return true;
		}else{
			this.importGet(false);
		}
		return false;
	}

	addGetParams(params){
		_.merge(this._getParams, params);
	}

	/*
	* Importiert einen URL-String und liest daraus die GET-Parameter ein.
	* https://www.sitepoint.com/get-url-parameters-with-javascript/
	*
	* @param string url (optional)
	*/
	importGet(url) {
	  	// get query string from url (optional) or window
	  	var queryString = url ? url.split('?')[1] : window.location.search.slice(1);
	  	queryString = decodeURIComponent(queryString);

	  	// we'll store the parameters here
	  	var obj = {};

	  	// if query string exists
	  	if (queryString) {

	    	// stuff after # is not part of query string, so get rid of it
	    	queryString = queryString.split('#')[0];

	    	// split our query string into its component parts
	    	var arr = queryString.split('&');

	    	for(var i=0; i < arr.length; i++) {
	     	 	// separate the keys and the values
	     	 	var a = arr[i].split('=');

	      		// in case params look like: list[]=thing1&list[]=thing2
	      		var paramNum = undefined;
	      		var paramName = a[0].replace(/\[\d*\]/, function(v) {
	      			paramNum = v.slice(1,-1);
	      			return '';
	      		});

	      		if(typeof paramName !== 'string'){
	      			continue;
	      		}

	      		// set parameter value (use 'true' if empty)
	      		var paramValue = typeof(a[1])==='undefined' ? true : a[1];

	      		if(typeof paramValue !== 'string'){
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
	exportGet(){
		return this.objectToQueryString(this.getParams);
	}

	get postParams(){
		return this._postParams;
	}

	set postParams(wert) {
		if(typeof wert === 'object'){
			this._postParams = wert;
			return true;
		}
		return false;
	}

	addPostParams(params){
		_.merge(this._postParams, params);
	}

	/**
	 * Liefert alle POST-Params als String
	 * @return string
	 */
	exportPost(){
		return this.objectToQueryString(this.postParams);
	}

	/**
	 * Wandelt ein Objekt in einen Query-String um
	 * @param  object a
	 * @return string
	 */
	objectToQueryString(a) {
		var prefix, s, add, name, r20, output;
		s = [];
		r20 = /%20/g;
		add = function (key, value) {
			// If value is a function, invoke it and return its value
			value = ( typeof value == 'function' ) ? value() : ( value == null ? "" : value );
			s[ s.length ] = encodeURIComponent(key) + "=" + encodeURIComponent(value);
		};
		if (a instanceof Array) {
			for (name in a) {
				add(name, a[name]);
			}
		} else {
			for (prefix in a) {
				this.buildParams(prefix, a[ prefix ], add);
			}
		}
		output = s.join("&").replace(r20, "+");
		return output;
	};

	buildParams(prefix, obj, add) {
		var name, i, l, rbracket;
		rbracket = /\[\]$/;
		if (obj instanceof Array) {
			for (i = 0, l = obj.length; i < l; i++) {
				if (rbracket.test(prefix)) {
					add(prefix, obj[i]);
				} else {
					this.buildParams(prefix + "[" + ( typeof obj[i] === "object" ? i : "" ) + "]", obj[i], add);
				}
			}
		} else if (typeof obj == "object") {
			// Serialize object item.
			for (name in obj) {
				this.buildParams(prefix + "[" + name + "]", obj[ name ], add);
			}
		} else {
			// Serialize scalar item.
			add(prefix, obj);
		}
	}

	/**
	 * Liefert die volle Anfrage-URL
	 *
	 * @param  boolean includeGetParams  Sollen die GET-Parameter mit angehängt werden? (Default: true)
	 * @return string url
	 */
	getUrl(includeGetParams){
		let ausgabe = this.url + this.pfad;
		if(includeGetParams || includeGetParams === undefined){
			ausgabe = ausgabe + '?' + this.exportGet();
		}
		return ausgabe;
	}

	fetch(options){
		let fetchOptions = {
			method: 'POST',
			headers: new Headers({
				"Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
				"X_REQUESTED_WITH": "XMLHttpRequest",
				"x-requested-with": "XMLHttpRequest",
				"HTTP_X_REQUESTED_WITH": "XMLHttpRequest"
			}),
			credentials: "same-origin",
			body: this.exportPost()
		};

		for(let key in options) {
			if(key === "body" && typeof options[key] === 'object'){
				fetchOptions.body = this.objectToQueryString(options[key]);
			}else{
				fetchOptions[key] = options[key];
			}
		}

		if(fetchOptions.method.toUpperCase() === 'GET'){
			// GET-Anfragen dürfen keinen Body haben!
			delete fetchOptions.body;
		}

		return fetch(this.getUrl(), fetchOptions);
	}
}

export default AjaxAnfrage;