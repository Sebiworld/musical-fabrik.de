import { ready, addClass, removeClass } from "./helpers.js";

ready(function () {
	const elements = document.querySelectorAll('[data-action="show-past-performances"]');
	for(const index in elements){
		const element = elements[index];
		if(typeof element !== 'object' || !(element instanceof Element)){
			continue;
		}
		const historyContainer = element.parentElement.querySelector('.past-performances');
		if(typeof historyContainer !== 'object' || !(historyContainer instanceof Element)){
			continue;
		}

		element.addEventListener('click', () => {
			addClass(element, 'd-none');
			removeClass(historyContainer, 'd-none');
		});
	}
});