import AjaxForm from './classes/AjaxForm.js';
import {ready} from './classes/helpers.js';

ready(function(){
	let formularElemente = document.querySelectorAll("form.form_template");
	Array.prototype.forEach.call(formularElemente, function(formularElement, index){
		try {
			if(typeof formularElement.getAttribute('data-page') !== 'string'){
				throw new Error('No page Id was passed.');
			}

			let ajaxForm = new AjaxForm(formularElement);

			// The evaluation of this form is done via an Ajax request to the Twack component:
			// ajaxForm.addGetParams({'twack-ajax': 1});
			ajaxForm.addPayload({
				// action: "getComponent",
				// component: "FormTemplate",
				page: formularElement.getAttribute('data-page'),
				// directory: "forms_component"
			});

		} catch (e) {
			console.log(e.name + ': ' + e.message, e);
		}
	});
});