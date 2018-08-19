(function($, window, document, undefined) {
	"use strict";

	// Letzter Wert, der als Title eingetragen wurde. Dadurch kann getrackt werden, ob manuell am Title etwas geändert wurde.
	var gesetzteWerte = {
		title: '',
		title_trennbar: ''
	};

	// Felder, aus denen der Title gebildet werden soll:
	var titleFelder = ['vorname', 'nachname'];

	/**
	* Ersetzt den Inhalt des Title-Feldes, wenn Vorname und/oder Nachname einen Inhalt haben und der Title nicht verändert wurde.
	*/
	function feldAusfuellen(name){
		if($('#Inputfield_'+name).val() != gesetzteWerte[name]) return false;
		var titleString = getTitleString();

		if(titleString.length > 0){
			$('#Inputfield_'+name).val(titleString);
			gesetzteWerte[name] = titleString;
		}
	}

	function getTitleString(){
		var titleString = '';

		for(var i = 0; i < titleFelder.length; i++){
			var titleFeld = titleFelder[i];
			var wert = $('#Inputfield_'+titleFeld).val();
			if(wert.length > 0){
				titleString += wert + ' ';
			}
		}
		titleString = titleString.trim();
		return titleString;
	}

	$(document).ready(function() {
		var selektorString = '';
		for(var i = 0; i < titleFelder.length; i++){
			var titleFeld = titleFelder[i];
			if(i > 0) selektorString += ', ';
			selektorString += '#Inputfield_'+titleFeld;
		}

		if($('#Inputfield_title').val() == getTitleString()){
			gesetzteWerte['title'] = $('#Inputfield_title').val();
			feldAusfuellen('title');
		}
		if($('#Inputfield_title_trennbar').length > 0 && $('#Inputfield_title_trennbar').val() == getTitleString()){
			gesetzteWerte['title_trennbar'] = $('#Inputfield_title_trennbar').val();
			feldAusfuellen('title_trennbar');
		}

		$(selektorString).on('input', function(){
			if($('#Inputfield_title').val() == getTitleString()){
				gesetzteWerte['title'] = $('#Inputfield_title').val();
			}
			feldAusfuellen('title');

			if($('#Inputfield_title_trennbar').length > 0){
				if($('#Inputfield_title_trennbar').val() == getTitleString()){
					gesetzteWerte['title_trennbar'] = $('#Inputfield_title_trennbar').val();
				}
				feldAusfuellen('title_trennbar');
			}
		});
	});

})(jQuery, window, document);