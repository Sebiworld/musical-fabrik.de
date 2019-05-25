import {matches, removeClass, addClass} from './hilfsfunktionen.js';
import {values, join} from "lodash";
import AjaxCall from './AjaxCall.js';
import Scrollinator from './Scrollinator.js';

export default class AjaxFormular {
	constructor(formularElement, options) {
		if(!matches(formularElement, 'form')){
			throw new Error('Es wurde kein Formular-Element übergeben.');
		}

		this.element = formularElement;

		this.ajaxCall = new AjaxCall();
		this.ajaxCall.method = 'POST';
		if(typeof options === 'object'){
			if(typeof options.payload === 'object'){
				this.ajaxCall.addPostParams(options.payload);
			}
		}

		// Scrollinator holen, wenn vorhanden:
		this.scrollinator = false;
		if (typeof Scrollinator === 'function'){
			this.scrollinator = new Scrollinator();
		}

		// Submit-Event des Formulars abfangen:
		this.element.addEventListener("submit", this.submit.bind(formularElement, this));
	}

	/*
	* POST-Parameter setzen
	*/
	get payload(){
		return this.ajaxCall.postParams;
	}

	set payload(params){
		this.ajaxCall.postParams = params;
	}

	addPayload(params){
		this.ajaxCall.addPostParams(params);
	}

	/*
	* GET-Parameter setzen
	*/
	get getParams(){
		return this.ajaxCall.getParams;
	}

	set getParams(params){
		this.ajaxCall.getParams = params;
	}

	addGetParams(params){
		this.ajaxCall.addGetParams(params);
	}

	/**
	* Prüft, ob das Feld einen Namen und einen Wert hat
	* @param  DOMElement  element
	* @return Boolean
	*/
	isValidElement(element){
		return element.name && element.value;
	}

	/**
	* Prüft, ob der Wert des Elements valide ist und gespeichert werden kann.
	* @param  DOMElement  element
	* @return Boolean
	*/
	isValidValue(element){
		return (!['checkbox', 'radio'].includes(element.type) || element.checked);
	}

	/**
	* Prüft, ob das übergebene Element eine Checkbox ist.
	* @param  DOMElement  element
	* @return Boolean
	*/
	isCheckbox(element){
		return element.type === 'checkbox';
	}

	/**
	* [isMultiSelect description]
	* @param  DOMElement  element
	* @return Boolean
	*/
	isMultiSelect(element){
		return element.options && element.multiple;
	}

	/**
	* Liefert alle ausgewählten Werte eines Select-Feldes.
	* @param  DOMElmements options
	* @return array<bool>
	*/
	getSelectValues(options){
		let werte = [];
		for(let option of options){
			if(option.selected){
				werte.push(option.value);
			}
		}
		return werte;
	}

	/**
	* Liest alle Werte aus dem Formular aus und bildet daraus ein JSON-Objekt.
	* @return object
	*/
	getValues(){
		let formularwerte = {};
		for(let inputElement of this.element.elements){
			// Make sure the element has the required properties and should be added.
			if (this.isValidElement(inputElement) && this.isValidValue(inputElement)) {
				// Some fields allow for more than one value, so we need to check if this
				// is one of those fields and, if so, store the values as an array.
				if (this.isCheckbox(inputElement)) {
					formularwerte[inputElement.name] = (formularwerte[inputElement.name] || []).concat(inputElement.value);
				} else if (this.isMultiSelect(inputElement)) {
					formularwerte[inputElement.name] = this.getSelectValues(inputElement);
				} else {
					formularwerte[inputElement.name] = inputElement.value;
				}
			}
		}
		return formularwerte;
	}

	formularHinweiseAktualisieren(hinweise){
		const obj = this;
		if(typeof hinweise !== 'object'){
			return false;
		}

		if(typeof hinweise.felder === 'object'){
			for(let index in hinweise.felder){
				let feldHinweis = hinweise.felder[index];
				if(typeof feldHinweis.name !== 'string'){
					continue;
				}
				let inputElement = this.element.querySelector("[name='" + feldHinweis.name + "']");

				if(!inputElement){
					if((feldHinweis.fehler && feldHinweis.fehler.length > 0) || (feldHinweis.erfolg && feldHinweis.erfolg.length > 0)){
						console.log("Ein Input-Element mit Hinweisen wurde nicht gefunden: ", feldHinweis);
					}
					continue;
				}

				if(feldHinweis.fehler !== undefined && feldHinweis.fehler && feldHinweis.fehler.length > 0){
					addClass(inputElement, 'is-invalid');

					if(typeof feldHinweis.fehler === 'string'){
						// Der Fehler wurde direkt als String angegeben
						this.feldHinweisAnzeigen(inputElement, {typ: 'fehler', text: feldHinweis.fehler});
					}else if(typeof feldHinweis.fehler === 'object'){
						if(Array.isArray(feldHinweis.fehler)){
							this.feldHinweisAnzeigen(inputElement, {typ: 'fehler', text: feldHinweis.fehler});
						}else{
							feldHinweis.typ = 'fehler';
							this.feldHinweisAnzeigen(inputElement, feldHinweis);
						}
					}
				}else{
					removeClass(inputElement, 'is-invalid');
					let elements = inputElement.querySelector('.invalid-feedback');
					if(typeof elements === 'object' && Array.isArray(elements)){
						Array.prototype.forEach.call(elements, function(el, i){
							el.parentNode.removeChild(el);
						});
					}
				}

				if(feldHinweis.erfolg !== undefined && feldHinweis.erfolg && feldHinweis.erfolg.length > 0){
					addClass(inputElement, 'is-valid');

					if(typeof feldHinweis.erfolg === 'string'){
						// Der Fehler wurde direkt als String angegeben
						this.feldHinweisAnzeigen(inputElement, {typ: 'erfolg', text: feldHinweis.erfolg});
					}else if(typeof feldHinweis.erfolg === 'object'){
						if(Array.isArray(feldHinweis.erfolg)){
							this.feldHinweisAnzeigen(inputElement, {typ: 'erfolg', text: feldHinweis.erfolg});
						}else{
							feldHinweis.typ = 'erfolg';
							this.feldHinweisAnzeigen(inputElement, feldHinweis);
						}
					}
				}else{
					removeClass(inputElement, 'is-valid');
					let elements = inputElement.querySelector('.valid-feedback');
					if(typeof elements === 'object' && Array.isArray(elements)){
						Array.prototype.forEach.call(elements, function(el, i){
							el.parentNode.removeChild(el);
						});
					}
				}
			}
		}

		// Allgemeine Hinweise werden über und unter dem Formular im dafür anzulegenden Container ".hinweise" angezeigt:
		let hinweisContainer = this.element.querySelector('.hinweise');
		if(hinweisContainer){
			// Hinweiscontainer leeren:
			hinweisContainer.innerHTML = '';

			// Neue Erfolg-Hinweise anzeigen:
			if(typeof hinweise.erfolg === 'object'){
				for(let index in hinweise.erfolg){
					let hinweis = hinweise.erfolg[index];
					let alertbox = document.createElement('div');
					alertbox.setAttribute('class', 'alert alert-success');
					alertbox.setAttribute('role', 'alert');
					alertbox.innerHTML = hinweis;
					hinweisContainer.appendChild(alertbox);
				}
			}

			// Neue Fehler-Hinweise anzeigen:
			if(typeof hinweise.fehler === 'object'){
				for(let index in hinweise.fehler){
					let hinweis = hinweise.fehler[index];
					let alertbox = document.createElement('div');
					alertbox.setAttribute('class', 'alert alert-danger');
					alertbox.setAttribute('role', 'alert');
					alertbox.innerHTML = hinweis;
					hinweisContainer.appendChild(alertbox);
				}
			}
		}

		if(obj.scrollinator){
			obj.scrollinator.hinscrollen(obj.element);
		}
	}

	/**
	* Zeigt am übergebenen Input einen Hinweistext an
	* @param  DOMElement element
	* @param  object hinweis
	* @return boolean
	*/
	feldHinweisAnzeigen(element, hinweis){
		if(!element){
			return false;
		}

		let inputContainer = element.closest('.input-container');
		if(!inputContainer){
			inputContainer = element.parentNode;
		}

		if(typeof hinweis !== 'object'){
			return false;
		}

		// Typ des Hinweises auslesen (typischerweise entweder "fehler" oder "erfolg"):
		let typ = 'fehler';
		if(typeof hinweis.typ === 'string'){
			typ = hinweis.typ;
		}

		// Hinweistext auslesen:
		let hinweistext = '';
		if(typeof hinweis.text === 'string'){
			hinweistext = hinweis.text;
		}else if(typeof hinweis.text === 'object'){
			if(!Array.isArray(hinweis.text)){
				// Der Text kann als Objekt angegeben werden. Die Keys werden bei der Ausgabe aber nicht berücksichtigt.
				hinweis.text = values(hinweis.text);
			}
			hinweistext = join(hinweis.text, '<br/>');
		}

		// Feedback-Container suchen oder erstellen:
		let feedbackContainer;
		if(typ === 'fehler'){
			// addClass(element, 'is-invalid');

			// Im Container-Element des Inputs wird nach einem Feedback-Container gesucht, in den die Hinweis-Meldung eingefügt werden kann:
			feedbackContainer = inputContainer.querySelector('.invalid-feedback');
			if(!feedbackContainer){
				// Noch kein Feedback-Container vorhanden - neu anlegen!
				feedbackContainer = document.createElement('div');
				feedbackContainer.setAttribute('class', 'invalid-feedback');
				inputContainer.appendChild(feedbackContainer);
			}
		}else if(typ === 'erfolg'){
			// addClass(element, 'is-valid');

			// Im Container-Element des Inputs wird nach einem Feedback-Container gesucht, in den die Hinweis-Meldung eingefügt werden kann:
			feedbackContainer = inputContainer.querySelector('.valid-feedback');
			if(!feedbackContainer){
				// Noch kein Feedback-Container vorhanden - neu anlegen!
				feedbackContainer = document.createElement('div');
				feedbackContainer.setAttribute('class', 'valid-feedback');
				inputContainer.appendChild(feedbackContainer);
			}
		}else{
			// Im Container-Element des Inputs wird nach einem Feedback-Container gesucht, in den die Hinweis-Meldung eingefügt werden kann:
			feedbackContainer = inputContainer.querySelector('.feedback');
			if(!feedbackContainer){
				// Noch kein Feedback-Container vorhanden - neu anlegen!
				feedbackContainer = document.createElement('div');
				feedbackContainer.setAttribute('class', 'feedback');
				inputContainer.appendChild(feedbackContainer);
			}
		}

		// Feedback-Container mit Inhalt befüllen:
		// if(feedbackContainer.innerHTML.length > 0){
		// 	feedbackContainer.innerHTML = feedbackContainer.innerHTML + '<br/>' + hinweistext;
		// }else{
			feedbackContainer.innerHTML = hinweistext;
		// }

		return true;
	}

	/**
	 * Sperrt das Formular für weitere Eingaben
	 */
	 formularSperren(){
	 	let formularFieldsets = this.element.querySelectorAll('.alle-formularelemente');
	 	Array.prototype.forEach.call(formularFieldsets, function(el, i){
	 		el.setAttribute('disabled', 'disabled');
	 	});

		// TODO: Optional Cookie speichern, um erneutes abschicken zu verhinden?
	}

	/**
	* Schickt die Formularinhalte an die Schnittstelle.
	*/
	submit(ajaxFormular, event){
		event.preventDefault();

		let formular = this;
		formular.querySelector('.form-control').classList.remove('is-invalid');
		formular.querySelector('.hinweise').innerHTML = '';

		// Ajax-Anfrage-Objekt klonen:
		let ajaxCall = ajaxFormular.ajaxCall.clone();

		// Eingegebene Werte zur Anfrage hinzufügen:
		ajaxCall.addPostParams(ajaxFormular.getValues());

		ajaxCall.fetch()
		.then(function(response){
			let json = response.json();
			if (response.status >= 200 && response.status < 300) {
				return json;
			}
			return json.then(Promise.reject.bind(Promise));
		})
		.then(function(response) {
			// console.log("Anfrage erfolgreich: ", response);
			ajaxFormular.formularHinweiseAktualisieren(response);
			ajaxFormular.formularSperren();
		}, function(response){
			// console.error('Fetch Error :-S', response);
			ajaxFormular.formularHinweiseAktualisieren(response);
		}).catch(function(err) {
			// console.error('Fetch Error Catch :-S', err);
			ajaxFormular.formularHinweiseAktualisieren(err);
		});

		return false;
	}
}