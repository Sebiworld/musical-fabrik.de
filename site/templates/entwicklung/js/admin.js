$(document).ready(function() {
	// Alle Felder, die eine Checkbox Feldname_aktivieren haben, mit der ein Feld aus- oder eingeblendet werden kann:
	var fields = [
		"zeitpunkt_freischalten_ab",
		"zeitpunkt_freischalten_bis",
		"apikey_abweisungsnachricht",
		"maximum_fehlversuche",
	];

	// Felder durchlaufen und für die Checkboxen on-change Handler setzen:
	fields.forEach(function(element, index, array) {
		var checkboxSelector =
			".Inputfield_" +
			element +
			"_aktivieren input[name=" +
			element +
			"_aktivieren]";

		if ($(checkboxSelector).length > 0) {
			$(checkboxSelector).on("change", function() {
				if ($(this).is(":checked")) {
					feldAnzeigen(element, true);
				} else {
					feldAnzeigen(element, false);
				}
			});
			feldAnzeigen(element, $(checkboxSelector).is(":checked"), true);
		}
	});

	/**
	 * Blendet ein Feld ein oder aus
	 *
	 * @param  string 	Feldname 	Der Name des zu behandelnden Feldes
	 * @param  boolean 	anzeigen 	Wenn false: Feld wird ausgeblendet
	 * @param  boolean	sofort   	Wenn true: show/hide werden statt fadeIn/fadeOut verwendet
	 */
	function feldAnzeigen(feldname, anzeigen, sofort) {
		if (anzeigen === undefined) anzeigen = true;

		if (anzeigen) {
			if (sofort) $(".Inputfield_" + feldname).show();
			$(".Inputfield_" + feldname).fadeIn();
			return;
		}

		if (sofort) $(".Inputfield_" + feldname).hide();
		$(".Inputfield_" + feldname).fadeOut();
		return;
	}
});
