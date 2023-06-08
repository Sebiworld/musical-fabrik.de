(function($, window, document, undefined) {
	"use strict";

	// Last value entered as Title. This can be used to track whether the title has been changed manually.
	var values = {
		title: '',
		title_separable: ''
	};

	// Fields from which the title is to be formed:
	var titleFields = ['first_name', 'last_name'];

	/**
	* Replaces the contents of the Title field if the first name and/or last name have contents and the Title has not been changed.
	*/
	function fillField(name){
		if($('#Inputfield_'+name).val() != values[name]) return false;
		var titleString = getTitleString();

		if(titleString.length > 0){
			$('#Inputfield_'+name).val(titleString);
			values[name] = titleString;
		}
	}

	function getTitleString(){
		var titleString = '';

		for(var i = 0; i < titleFields.length; i++){
			var titleField = titleFields[i];
			var value = $('#Inputfield_'+titleField).val();
			if(typeof value === 'string' && value.length > 0){
				titleString += value + ' ';
			}
		}
		titleString = titleString.trim();
		return titleString;
	}

	$(document).ready(function() {
		var selectorString = '';
		for(var i = 0; i < titleFields.length; i++){
			var titleFeld = titleFields[i];
			if(i > 0) selectorString += ', ';
			selectorString += '#Inputfield_'+titleFeld;
		}

		if($('#Inputfield_title').val() == getTitleString()){
			values.title = $('#Inputfield_title').val();
			fillField('title');
		}
		if($('#Inputfield_title_separable').length > 0 && $('#Inputfield_title_separable').val() == getTitleString()){
			values.title_separable = $('#Inputfield_title_separable').val();
			fillField('title_separable');
		}

		$(selectorString).on('input', function(){
			if($('#Inputfield_title').val() == getTitleString()){
				values.title = $('#Inputfield_title').val();
			}
			fillField('title');

			if($('#Inputfield_title_separable').length > 0){
				if($('#Inputfield_title_separable').val() == getTitleString()){
					values.title_separable = $('#Inputfield_title_separable').val();
				}
				fillField('title_separable');
			}
		});
	});

})(jQuery, window, document);