<?php
namespace ProcessWire;

/**
 * Stellt Methoden zur Formatierung und Filterung der Ausgabe von Übersichtsseiten zur Verfügung.
 */
class UebersichtsseitenProvider extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
	}

	public function formatieren(PageArray $seiten, $args = array()) {
		foreach ($seiten as &$seite) {
			// Prüfen, ob der Beitrag für den Nutzer sichtbar ist:
			if (!$seite->viewable()) {
				$seiten->remove($seite);
			}

			// Projektzugehörigkeit:
			$projektseite = $seite->closest('template.name^=projekt');
			if ($projektseite instanceof Page && $projektseite->id) {
				$seite->projektseite = $projektseite;
			}

			if (isset($args['limit']) && $seite->template->hasField('einleitung')) {
				$limit = $args['limit'];
				$endstr = '&nbsp;…';
				if (isset($args['endstr'])) {
					$endstr = $args['endstr'];
				}
				$seite->einleitung = Twack::wordLimiter($seite->einleitung, $limit, $endstr);
			}

			if ($seite->template->hasField('autoren') && $seite->autoren instanceof PageArray) {
				$autoren = array();
				foreach ($seite->autoren as $autor) {
					$autoren[] = $autor->vorname . ' ' . $autor->nachname;
				}
				$seite->autoren_lesbar = implode(' & ', $autoren);
			}
		}
		return $seiten;
	}

	public function formatierePage(Page $seite, $args = array()) {
		// Prüfen, ob der Beitrag für den Nutzer sichtbar ist:
		if (!$seite->viewable()) {
			return false;
		}

		// Projektzugehörigkeit:
		$projektseite = $seite->closest('template.name^=projekt');
		if ($projektseite instanceof Page && $projektseite->id) {
			$seite->projektseite = $projektseite;

			if($projektseite->farbe){
				$seite->farbe = $projektseite->farbe;
			}
		}

		if (isset($args['limit']) && $seite->template->hasField('einleitung')) {
			$limit = $args['limit'];
			$endstr = '&nbsp;…';
			if (isset($args['endstr'])) {
				$endstr = $args['endstr'];
			}
			$seite->einleitung = Twack::wordLimiter($seite->einleitung, $limit, $endstr);
		}

		if ($seite->template->hasField('autoren') && $seite->autoren instanceof PageArray) {
			$autoren = array();
			foreach ($seite->autoren as $autor) {
				$autoren[] = $autor->vorname . ' ' . $autor->nachname;
			}
			$seite->autoren_lesbar = implode(' & ', $autoren);
		}

		return $seite;
	}
}
