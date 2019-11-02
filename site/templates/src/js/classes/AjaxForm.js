import { matches, removeClass, addClass } from './helpers.js';
import { values, join } from "lodash-es";
import AjaxCall from './AjaxCall.js';
import Scrollinator from './Scrollinator.js';

export default class AjaxForm {
	constructor(formElement, options) {
		if (!matches(formElement, 'form')) {
			throw new Error('No form-element found.');
		}

		this.element = formElement;

		let parser = document.createElement('a');
		parser.href = formElement.action;
		let pathname = parser.pathname;

		if (pathname[0] != '/') {
			pathname = '/' + pathname; // Fix IE11
		}

		this.ajaxCall = new AjaxCall({
			method: 'POST',
			path: '/api/page' + pathname,
			headers: {
				'X-API-KEY': 'SEawMksSM8AAKnbAroSyU'
			}
		});
		if (typeof options === 'object') {
			if (typeof options.payload === 'object') {
				this.ajaxCall.addPostParams(options.payload);
			}
		}

		// Get Scrollinator, if available:
		this.scrollinator = false;
		if (typeof Scrollinator === 'function') {
			this.scrollinator = new Scrollinator();
		}

		// Submit event of the form:
		this.element.addEventListener("submit", this.submit.bind(formElement, this));
	}

	/*
	* Set POST-Parameter
	*/
	get payload() {
		return this.ajaxCall.postParams;
	}

	set payload(params) {
		this.ajaxCall.postParams = params;
	}

	addPayload(params) {
		this.ajaxCall.addPostParams(params);
	}

	/*
	* Set GET-Parameter
	*/
	get getParams() {
		return this.ajaxCall.getParams;
	}

	set getParams(params) {
		this.ajaxCall.getParams = params;
	}

	addGetParams(params) {
		this.ajaxCall.addGetParams(params);
	}

	/**
	* Checks whether the field has a name and a value.
	* @param  DOMElement  element
	* @return Boolean
	*/
	isValidElement(element) {
		return element.name && element.value;
	}

	/**
	* Checks if the value of the element is valid and can be saved.
	* @param  DOMElement  element
	* @return Boolean
	*/
	isValidValue(element) {
		return (!['checkbox', 'radio'].includes(element.type) || element.checked);
	}

	/**
	* Checks whether the passed element is a checkbox.
	* @param  DOMElement  element
	* @return Boolean
	*/
	isCheckbox(element) {
		return element.type === 'checkbox';
	}

	/**
	* Is the element multi-selectable?
	* @param  DOMElement  element
	* @return Boolean
	*/
	isMultiSelect(element) {
		return element.options && element.multiple;
	}

	/**
	* Returns all selected values of a select field.
	* @param  DOMElmements options
	* @return array<bool>
	*/
	getSelectValues(options) {
		let values = [];
		for (let option of options) {
			if (option.selected) {
				values.push(option.value);
			}
		}
		return values;
	}

	/**
	* Reads all values from the form and creates a JSON object from them.
	* @return object
	*/
	getValues() {
		let formvalues = {};
		for (let inputElement of this.element.elements) {
			// Make sure the element has the required properties and should be added.
			if (this.isValidElement(inputElement) && this.isValidValue(inputElement)) {
				// Some fields allow for more than one value, so we need to check if this
				// is one of those fields and, if so, store the values as an array.
				if (this.isCheckbox(inputElement)) {
					formvalues[inputElement.name] = (formvalues[inputElement.name] || []).concat(inputElement.value);
				} else if (this.isMultiSelect(inputElement)) {
					formvalues[inputElement.name] = this.getSelectValues(inputElement);
				} else {
					formvalues[inputElement.name] = inputElement.value;
				}
			}
		}
		return formvalues;
	}

	refreshFormEvaluation(alerts) {
		const obj = this;
		if (typeof alerts !== 'object') {
			return false;
		}

		if (typeof alerts.fields === 'object') {
			for (let index in alerts.fields) {
				let fieldAlert = alerts.fields[index];
				if (typeof fieldAlert.name !== 'string') {
					continue;
				}
				let inputElement = this.element.querySelector("[name='" + fieldAlert.name + "']");

				if (!inputElement) {
					if ((fieldAlert.error && fieldAlert.error.length > 0) || (fieldAlert.success && fieldAlert.success.length > 0)) {
						console.log("An input element with notes was not found: ", fieldAlert);
					}
					continue;
				}

				if (fieldAlert.error !== undefined && fieldAlert.error && fieldAlert.error.length > 0) {
					addClass(inputElement, 'is-invalid');

					if (typeof fieldAlert.error === 'string') {
						// The error was specified directly as a string.
						this.showFieldAlert(inputElement, { typ: 'error', text: fieldAlert.error });
					} else if (typeof fieldAlert.error === 'object') {
						if (Array.isArray(fieldAlert.error)) {
							this.showFieldAlert(inputElement, { typ: 'error', text: fieldAlert.error });
						} else {
							fieldAlert.typ = 'error';
							this.showFieldAlert(inputElement, fieldAlert);
						}
					}
				} else {
					removeClass(inputElement, 'is-invalid');
					let elements = inputElement.querySelector('.invalid-feedback');
					if (typeof elements === 'object' && Array.isArray(elements)) {
						Array.prototype.forEach.call(elements, function (el, i) {
							el.parentNode.removeChild(el);
						});
					}
				}

				if (fieldAlert.success !== undefined && fieldAlert.success && fieldAlert.success.length > 0) {
					addClass(inputElement, 'is-valid');

					if (typeof fieldAlert.success === 'string') {
						// The error was specified directly as a string.
						this.showFieldAlert(inputElement, { typ: 'success', text: fieldAlert.success });
					} else if (typeof fieldAlert.success === 'object') {
						if (Array.isArray(fieldAlert.success)) {
							this.showFieldAlert(inputElement, { typ: 'success', text: fieldAlert.success });
						} else {
							fieldAlert.typ = 'success';
							this.showFieldAlert(inputElement, fieldAlert);
						}
					}
				} else {
					removeClass(inputElement, 'is-valid');
					let elements = inputElement.querySelector('.valid-feedback');
					if (typeof elements === 'object' && Array.isArray(elements)) {
						Array.prototype.forEach.call(elements, function (el, i) {
							el.parentNode.removeChild(el);
						});
					}
				}
			}
		}

		// General notes are displayed above and below the form in the ".alerts" container to be created for this purpose:
		let alertsContainer = this.element.querySelector('.alerts');
		if (alertsContainer) {
			// Empty the hint container:
			alertsContainer.innerHTML = '';

			// Display new success notes:
			if (typeof alerts.success === 'object') {
				for (let index in alerts.success) {
					let alert = alerts.success[index];
					let alertbox = document.createElement('div');
					alertbox.setAttribute('class', 'alert alert-success');
					alertbox.setAttribute('role', 'alert');
					alertbox.innerHTML = alert;
					alertsContainer.appendChild(alertbox);
				}
			}

			// Display new error messages:
			if (typeof alerts.error === 'object') {
				for (let index in alerts.error) {
					let alert = alerts.error[index];
					let alertbox = document.createElement('div');
					alertbox.setAttribute('class', 'alert alert-danger');
					alertbox.setAttribute('role', 'alert');
					alertbox.innerHTML = alert;
					alertsContainer.appendChild(alertbox);
				}
			}
		}

		if (obj.scrollinator) {
			obj.scrollinator.scrollTo(obj.element);
		}
	}

	/**
	* Displays a hint text at the input transferred
	* @param  DOMElement element
	* @param  object alert
	* @return boolean
	*/
	showFieldAlert(element, alert) {
		if (!element) {
			return false;
		}

		let inputContainer = element.closest('.input-container');
		if (!inputContainer) {
			inputContainer = element.parentNode;
		}

		if (typeof alert !== 'object') {
			return false;
		}

		// Read type of note (typically either "error" or "success"):
		let typ = 'error';
		if (typeof alert.typ === 'string') {
			typ = alert.typ;
		}

		// Read out note text:
		let alerttext = '';
		if (typeof alert.text === 'string') {
			alerttext = alert.text;
		} else if (typeof alert.text === 'object') {
			if (!Array.isArray(alert.text)) {
				// The text can be specified as an object. However, the keys are not taken into account in the output.
				alert.text = values(alert.text);
			}
			alerttext = join(alert.text, '<br/>');
		}

		// Search for or create feedback containers:
		let feedbackContainer;
		if (typ === 'error') {
			// addClass(element, 'is-invalid');

			// In the container element of the input, the system searches for a feedback container in which the note message can be inserted:
			feedbackContainer = inputContainer.querySelector('.invalid-feedback');
			if (!feedbackContainer) {
				// No feedback container available yet - create a new one!
				feedbackContainer = document.createElement('div');
				feedbackContainer.setAttribute('class', 'invalid-feedback');
				inputContainer.appendChild(feedbackContainer);
			}
		} else if (typ === 'success') {
			// addClass(element, 'is-valid');

			// In the container element of the input, the system searches for a feedback container in which the note message can be inserted:
			feedbackContainer = inputContainer.querySelector('.valid-feedback');
			if (!feedbackContainer) {
				// No feedback container available yet - create a new one!
				feedbackContainer = document.createElement('div');
				feedbackContainer.setAttribute('class', 'valid-feedback');
				inputContainer.appendChild(feedbackContainer);
			}
		} else {
			// In the container element of the input, the system searches for a feedback container in which the note message can be inserted:
			feedbackContainer = inputContainer.querySelector('.feedback');
			if (!feedbackContainer) {
				// No feedback container available yet - create a new one!
				feedbackContainer = document.createElement('div');
				feedbackContainer.setAttribute('class', 'feedback');
				inputContainer.appendChild(feedbackContainer);
			}
		}

		// Fill feedback container with content:
		// if(feedbackContainer.innerHTML.length > 0){
		// 	feedbackContainer.innerHTML = feedbackContainer.innerHTML + '<br/>' + alerttext;
		// }else{
		feedbackContainer.innerHTML = alerttext;
		// }

		return true;
	}

	/**
	 * Locks the form for further entries.
	 */
	lockForm() {
		let formularFieldsets = this.element.querySelectorAll('.all-formelements');
		Array.prototype.forEach.call(formularFieldsets, function (el, i) {
			el.setAttribute('disabled', 'disabled');
		});

		// TODO: Optionally save cookie to prevent sending it again?
	}

	/**
	* Sends the form contents to the interface.
	*/
	submit(ajaxForm, event) {
		event.preventDefault();

		let formular = this;
		formular.querySelector('.form-control').classList.remove('is-invalid');
		formular.querySelector('.alerts').innerHTML = '';

		// Clone Ajax query object:
		let ajaxCall = ajaxForm.ajaxCall.clone();

		// Add entered values to the query:
		ajaxCall.addPostParams(ajaxForm.getValues());

		ajaxCall.fetch()
			.then(function (response) {
				let json = response.json();
				if (response.status >= 200 && response.status < 300) {
					return json;
				}
				return json.then(Promise.reject.bind(Promise));
			})
			.then(function (response) {
				// console.log("Request successful: ", response);
				ajaxForm.refreshFormEvaluation(response);
				ajaxForm.lockForm();
			}, function (response) {
				// console.error('Fetch Error :-S', response);
				ajaxForm.refreshFormEvaluation(response);
			}).catch(function (err) {
				// console.error('Fetch Error Catch :-S', err);
				ajaxForm.refreshFormEvaluation(err);
			});

		return false;
	}
}