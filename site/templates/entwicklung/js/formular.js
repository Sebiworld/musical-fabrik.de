import AjaxFormular from './classes/AjaxFormular.js';
import {ready} from './classes/hilfsfunktionen.js';

ready(function(){
	let formularElemente = document.querySelectorAll("form.template-formular");
	Array.prototype.forEach.call(formularElemente, function(formularElement, index){
		try {
			if(typeof formularElement.getAttribute('data-seite') !== 'string'){
				throw new Error('Es wurde keine Seiten-ID übergeben.');
			}

			let ajaxFormular = new AjaxFormular(formularElement);

			// Die Auswertung dieses Formulars erfolgt über eine Ajax-Anfrage an die Twack-Komponente:
			ajaxFormular.addGetParams({'twack-ajax': 1});
			ajaxFormular.addPayload({
				action: "getComponent",
				component: "TemplateFormular",
				page: formularElement.getAttribute('data-seite'),
				directory: "formulare"
			});

		} catch (e) {
			console.log(e.name + ': ' + e.message, e);
		}
	});
});