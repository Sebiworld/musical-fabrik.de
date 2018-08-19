$(document).ready(function() {

    // process the form
    $('.template-formular-wrapper form.template-formular').submit(function(event) {

    	var formular = $(this);

    	formular.find('.form-control').removeClass('is-invalid');
    	formular.find('.hinweise').html('');

		$.ajax({
			type: 'POST',
			url: formular.attr('action'),
			data: formular.serialize()
		}).done(function(data){
			// console.log("Erfolg!", data);
			var antwort = $.parseJSON(data);

			var text = "Das Formular wurde erfolgreich verarbeitet.";
			if(typeof antwort.text === 'string'){
				text = antwort.text;
			}

			formular.find('.hinweise').append('<div class="alert alert-success" role="alert">' + text + '</div>');
			formular.find('.btn-formular-senden').remove();
			formular.find('input').attr('readonly', 'readonly');
			formular.find('textarea').attr('readonly', 'readonly').attr('disabled', 'disabled');
			formular.find('input[type="checkbox"]').attr('disabled', 'disabled');

			if(typeof $.sebi.scrollskript.hinscrollen === 'function'){
				$.sebi.scrollskript.hinscrollen(formular, {scrollOffset: 80});
			}

		}).fail(function(data){
			// console.log("Fehler!", data);
			var antwort = $.parseJSON(data.responseText);
			if(typeof antwort === 'object' && typeof antwort.fehler === 'object'){
				if(Array.isArray(antwort.fehler.felder)){
					// "Fehlende Felder" wurden vom Server geliefert:
					antwort.fehler.felder.forEach(function(element){
						formular.find('.form-control[name="' + element + '"]').addClass('is-invalid');
					});
				}

				for(var attributname in antwort.fehler){
					var fehler = antwort.fehler[attributname];
					if(attributname !== 'felder' && typeof fehler === 'object' && typeof fehler.text === 'string'){
						formular.find('.hinweise').append('<div class="alert alert-danger" role="alert">' + fehler.text + '</div>');
					}
				}

				if(typeof $.sebi.scrollskript.hinscrollen === 'function'){
					$.sebi.scrollskript.hinscrollen(formular, {scrollOffset: 80});
				}
			}
		});

    	event.preventDefault();
    });
});