(function($, window, document, undefined) {
	"use strict";
	/**
	* Liefert alle Elemente, die sich in der selben Spalte befinden (bis auf .alle)
	* @return array<domelement>
	*/
	function getReiheSiblings(ausgangszelle){
		var elemente = [];
		ausgangszelle.siblings(".checkbox-zelle").not('.alle').each(function(){
			elemente.push(this);
		});
		return elemente;
	}

	/**
	* Liefert die .alle-Zellen der Reihe
	*/
	function getReiheAlle(ausgangszelle){
		var reihe = ausgangszelle.closest('.reihe');
		return reihe.find(".checkbox-zelle.alle");
	}

	function getUntergeordneteReihen(ausgangsreihe, nurDirekt){
		var reihen = [];

		ausgangsreihe.nextUntil().each(function(){
			// Wenn kleinere Ebene: Abbruch
			if(parseInt($(this).attr("data-ebene")) < (parseInt(ausgangsreihe.attr('data-ebene')) + 1)) return false;
			if(!nurDirekt || (parseInt($(this).attr("data-ebene")) == parseInt(ausgangsreihe.attr('data-ebene')) + 1)){
				reihen.push(this);
			}
		});

		return reihen;
	}

	/**
	* Liefert alle Zellen, die sich auf den untergeodneten Ebenen, aber in der selben Spalte befinden.
	* @return array<domelement>
	*/
	function getSpaltenChildren(ausgangszelle){
		var ausgangsreihe = ausgangszelle.closest('.reihe');
		var spalte = ausgangszelle.attr('data-spalte');
		var elemente = [];

		var reihen = getUntergeordneteReihen(ausgangsreihe, false);

		for(var i = 0; i < reihen.length; i++){
			var zelle = $(reihen[i]);
			$(zelle).find('.checkbox-zelle[data-spalte="' + spalte + '"]').each(function(){
				elemente.push(this);
			});
		}

		return elemente;
	}

	/**
	* Liefert alle Zellen, die sich auf der gleichen Ebene und darunter, sowie in der selben Spalte befinden.
	* @return array<domelement>
	*/
	function getSpaltenSiblings(ausgangszelle){
		var reihe = ausgangszelle.closest('.reihe');
		var spalte = ausgangszelle.attr('data-spalte');
		var elemente = [];

		reihe.nextUntil().each(function(){
			// Wenn höhere Ebene: Abbruch
			if ($(this).attr("data-ebene") < reihe.attr('data-ebene')) return false;
			$(this).find('.checkbox-zelle[data-spalte="' + spalte + '"]').each(function(){
				elemente.push(this);
			});
		});

		reihe.prevUntil().each(function(){
			// Wenn höhere Ebene: Abbruch
			if ($(this).attr("data-ebene") < reihe.attr('data-ebene')) return false;
			$(this).find('.checkbox-zelle[data-spalte="' + spalte + '"]').each(function(){
				elemente.push(this);
			});
		});

		return elemente;
	}

	/**
	* Liefert die Elternzelle
	*/
	function getSpaltenParent(ausgangszelle){
		var reihe = ausgangszelle.closest('.reihe');
		var spalte = ausgangszelle.attr('data-spalte');
		var element = false;

		reihe.prevUntil().each(function(){
			if ($(this).attr("data-ebene") < reihe.attr('data-ebene')){
				element = $(this).find('.checkbox-zelle[data-spalte="' + spalte + '"]');
				return false;
			}
		});

		return element;
	}

	/**
	* Liefert die Checkbox zu einer Zelle
	*/
	function getCheckbox(ausgangszelle){
		return ausgangszelle.find("input[type='checkbox']").first();
	}

	/**
	* Prüft, ob die Checkbox in einer Zelle Aktiviert ist
	*/
	function isChecked(ausgangszelle){
		return !!getCheckbox(ausgangszelle).prop("checked");
	}

	function check(ausgangszelle, uncheck){
		getCheckbox(ausgangszelle).prop("checked", !uncheck);
		return !uncheck;
	}
	function uncheck(ausgangszelle){
		check(ausgangszelle, true);
		return false;
	}

	/**
	* Aktiviert und deaktiviert die richtigen Felder in der Reihe der angeklickten Checkbox
	*/
	function behandleCheckboxReihe(ausgangszelle, changed){
		var zellen = getReiheSiblings(ausgangszelle);

		var checked = isChecked(ausgangszelle);
		var alle = !!ausgangszelle.hasClass('alle');

		// Prüfen, ob alle Zellen der Reihe aktiviert sind:
		var alleAktiviert = checked;

		for(var i = 0; i < zellen.length; i++){
			var zelle = $(zellen[i]);
			if(alle){
				check(zelle, !checked);
				continue;
			}
			if(!isChecked(zelle)){
				alleAktiviert = false;
			}
		}

		if(alleAktiviert){
			check(getReiheAlle(ausgangszelle));
		}else{
			// Wenn nicht alle Zellen der Reihe aktiviert sind: .alle deaktivieren
			uncheck(getReiheAlle(ausgangszelle));
		}
	}

	/**
	* Aktiviert und deaktiviert die richtigen Felder in der Spalte der angeklickten Checkbox
	*/
	function behandleCheckboxSpalte(ausgangszelle, changed){
		var gecheckt = isChecked(ausgangszelle);

		var alle = !!ausgangszelle.hasClass('alle');
		var elternElement = getSpaltenParent(ausgangszelle);

		// Zähl- und Temp-Variablen:
		var i, zelle;

		// Alle untergeordneten Elemente an den Zellen-Status anpassen (wenn auf diese Zelle geklickt wurde):
		var kindzellen = getSpaltenChildren(ausgangszelle);
		for(i = 0; i < kindzellen.length; i++){
			zelle = $(kindzellen[i]);

			if(changed){
				// Diese Zelle wurde angeklickt. Alle Unterzellen müssen deshalb ihren Status annehmen.
				check(zelle, !gecheckt);
			}else if(gecheckt){
				// Die Ausgangszelle wurde nicht direkt angeklick. Deshalb müssen nur die Unterelemente aktiviert werden, wenn diese Zelle aktiviert ist:
				check(zelle, false);
			}
			behandleCheckboxReihe(zelle);
		}

		if(elternElement){
			// Prüfen, ob alle Zellen der Spalte aktiviert sind. Das wirkt sich dann auf das übergeordnete Element aus
			var alleAktiviert = gecheckt;

			var zellen = getSpaltenSiblings(ausgangszelle);
			for(i = 0; i < zellen.length; i++){
				zelle = $(zellen[i]);
				if(!isChecked(zelle)){
					alleAktiviert = false;
				}
			}

			if(alleAktiviert){
				check(elternElement);
			}else{
				// Wenn nicht alle Zellen der Reihe aktiviert sind: .alle deaktivieren
				uncheck(elternElement);
			}
			behandleCheckboxReihe(elternElement);
		}
	}

	/**
	* Behandelt den Klick auf eine einzelne Checkbox-Zelle.
	*/
	function behandleCheckboxZelle(ausgangszelle, changed){
		behandleCheckboxReihe(ausgangszelle, changed);
		behandleCheckboxSpalte(ausgangszelle, changed);
	}

	/**
	* Behandelt alle Checkboxen nacheinander (wird initial beim Laden der Seite durchgeführt).
	*/
	function behandleAlleCheckboxen(){
		$("table.rollen-tabelle .checkbox-zelle").not('.alle').each(function(){
			behandleCheckboxZelle($(this));
		});
	}

	function klappeReiheAuf(ausgangsreihe){
		var reihen = getUntergeordneteReihen(ausgangsreihe, true);
		if(reihen.length > 0){
			ausgangsreihe.removeClass('zugeklappt').addClass('ausgeklappt');

			if(ausgangsreihe.find(".klapp-icon").length <= 0){
				ausgangsreihe.find(".beschriftungs-zelle").append('<i class="klapp-icon fa" aria-hidden="true"></i>');
			}
			ausgangsreihe.find(".klapp-icon").attr('class', '').addClass("klapp-icon fa fa-caret-up");

			for(var i = 0; i < reihen.length; i++){
				var reihe = $(reihen[i]);
				reihe.removeClass('geschlossen').show();
			}
		}
	}

	function klappeReiheZu(ausgangsreihe){
		var reihen = getUntergeordneteReihen(ausgangsreihe, true);
		if(reihen.length > 0){
			ausgangsreihe.removeClass('ausgeklappt').addClass('zugeklappt');

			if(ausgangsreihe.find(".klapp-icon").length <= 0){
				ausgangsreihe.find(".beschriftungs-zelle").append('<i class="klapp-icon fa" aria-hidden="true"></i>');
			}
			ausgangsreihe.find(".klapp-icon").attr('class', '').addClass("klapp-icon fa fa-caret-down");

			for(var i = 0; i < reihen.length; i++){
				var reihe = $(reihen[i]);
				reihe.addClass('geschlossen').hide();
				if(reihe.hasClass('ausgeklappt')){
					klappeReiheZu(reihe);
				}
			}
		}
	}

	function setzeKlappReihen(){
		$("table.rollen-tabelle .reihe[data-ebene]").each(function(){
			klappeReiheZu($(this));
		});
	}

	$(document).ready(function() {
		setzeKlappReihen();
		behandleAlleCheckboxen();

		$("table.rollen-tabelle .checkbox-zelle input[type='checkbox']").change(function() {
			var zelle = $(this).closest('.checkbox-zelle');
			behandleCheckboxZelle(zelle, true);
		});

		$("table.rollen-tabelle .reihe .beschriftungs-zelle").click(function(){
			var reihe = $(this).closest('.reihe');
			if(reihe.hasClass('ausgeklappt')){
				klappeReiheZu(reihe);
			}else if(reihe.hasClass('zugeklappt')){
				klappeReiheAuf(reihe);
			}
		});
	});

})(jQuery, window, document);