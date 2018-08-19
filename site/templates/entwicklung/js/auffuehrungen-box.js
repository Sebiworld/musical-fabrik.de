(function($, window, document, undefined) {
	"use strict";

	function init(){
		// Dropdowns:
		$('[data-funktion="vergangene-veranstaltungen-anzeigen"]').on('click', function () {
			$(this).hide().parent().find('.vergangen').fadeIn();
		});

	}
	init();

})(jQuery, window, document);