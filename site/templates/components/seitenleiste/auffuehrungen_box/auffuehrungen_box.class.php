<?php
namespace ProcessWire;

class AuffuehrungenBox extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
		$tage = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");

		$projektseite = $this->getGlobalParameter('projektseite');
		if (isset($args['projektseite']) && $args['projektseite'] instanceof Page && $args['projektseite']->id) {
			$projektseite = $args['projektseite'];
		}
		if (!($projektseite instanceof Page) || !$projektseite->id) {
			throw new ComponentNotInitializedException('AuffuehrungenBox', 'Es wurde keine Projektseite gefunden.');
		}

		if (!isset($args['nutzeFeld']) || !is_string($args['nutzeFeld']) || empty($args['nutzeFeld'])) {
			$args['nutzeFeld'] = 'zeitpunkt_von';
		}
		if (!wire('fields')->get($args['nutzeFeld'])) {
			throw new ComponentNotInitializedException('AuffuehrungenBox', 'Es existiert kein Feld mit dem Namen "'.$args['nutzeFeld'].'". ');
		}

		$auffuehrungskategorie = wire('pages')->get('template.name=termin_kategorie, name=auffuehrung, include=all');
		if (!($auffuehrungskategorie->id.'')) {
			throw new ComponentNotInitializedException('AuffuehrungenBox', 'Es wurde keine Aufführungskategorie-Seite gefunden.');
		}

		$termineService = $this->getService('TermineService');
		$termine = $termineService->getTermine(array(
			'gastnutzer' => true,
			'kategorien' => array($auffuehrungskategorie->id)
		));

		// $termine = $termineService->getTermine('fuer_gaeste_freigegeben=1, termin_kategorien='.$auffuehrungskategorie->id);

		$this->titel = 'Aufführungen';
		if (isset($args['titel']) && !empty($args['titel'])) {
			$this->titel = str_replace(array("\n", "\r"), '', $args['titel']);
		}

		$auffuehrungen = array();
		$auffuehrungenAlt = array();
		foreach ($termine->termine as $auffuehrung) {
			foreach ($auffuehrung->zeitraeume->sort('-zeitpunkt_von') as $zeitraum) {
				if (!$zeitraum->template->hasField($args['nutzeFeld']) || $zeitraum->getUnformatted($args['nutzeFeld']) == 0) {
					continue;
				}

				$tmp = new \StdClass();
				$tmp->timestamp = $zeitraum->getUnformatted($args['nutzeFeld']);
				$tmp->datum = date('d.m.Y', $zeitraum->getUnformatted($args['nutzeFeld']));
				$tmp->uhrzeit = date('H:i', $zeitraum->getUnformatted($args['nutzeFeld']));
				$tmp->wochentag = $tage[date('w', $zeitraum->getUnformatted($args['nutzeFeld']))];
				$tmp->staffeln = $auffuehrung->staffeln;
				$tmp->besetzung = "";
				$tmp->kategorien = $zeitraum->termin_kategorien;

				if ($zeitraum->template->hasField('besetzung') && $zeitraum->besetzung instanceof Page && $zeitraum->besetzung->id) {
					$tmp->besetzung .= $zeitraum->besetzung->title;
				}

				if ($zeitraum->getUnformatted($args['nutzeFeld']) < time()) {
					// Die Veranstaltung ist vergangen
					array_unshift($auffuehrungenAlt, $tmp);
				} else {
					// Die Veranstaltung liegt in der Zukunft
					$auffuehrungen[] = $tmp;
				}
			}
		}

		if (count($auffuehrungen) > 1) {
			usort($auffuehrungen, function ($a, $b) {
				return $a->timestamp > $b->timestamp;
			});
		}

		if (count($auffuehrungenAlt) > 1) {
			usort($auffuehrungenAlt, function ($a, $b) {
				return $a->timestamp < $b->timestamp;
			});
		}

		$this->auffuehrungen = $auffuehrungen;
		$this->auffuehrungenAlt = $auffuehrungenAlt;

		if ($projektseite->template->hasField('seite') && $projektseite->seite->id) {
			$this->ticketSeite = $projektseite->seite;
		}

		// if (($this->auffuehrungen && count($this->auffuehrungen) > 0) || ($this->auffuehrungenAlt && count($this->auffuehrungenAlt) > 0)) {
			// $this->addScript('auffuehrungen-box.js', array(
			// 	'path'     => wire('config')->urls->templates . 'assets/js/',
			// 	'absolute' => true
			// ));
			// $this->addScript('legacy/auffuehrungen-box.js', array(
			// 	'path'     => wire('config')->urls->templates . 'assets/js/',
			// 	'absolute' => true
			// ));
		// }
	}
}
