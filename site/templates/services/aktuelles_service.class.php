<?php
namespace ProcessWire;

/**
 * Stellt Methoden zum Auslesen von Beiträgen zur Verfügung
 */
class AktuellesService extends TwackComponent {

	protected $projektseite = false;
	protected $uebersichtsseitenService = false;

	public function __construct($args) {
		parent::__construct($args);

		if ($this->startsWith($this->page->template->name, 'projekt')) {
			$this->projektseite = $this->page;
		} else {
			$this->projektseite = $this->page->closest('template.name^=projekt');
		}

		$this->uebersichtsseitenService = $this->getService('UebersichtsseitenService');
	}

	public function getAktuellesSeite() {
		$aktuellesSeite = wire('pages')->get('/')->children('template.name=beitraege_uebersicht')->first();
		if ($this->projektseite instanceof Page && $this->projektseite->id) {
			$funde = $this->projektseite->find('template.name=beitraege_uebersicht');
			if ($funde->count > 0) {
				$aktuellesSeite = $funde->first();
			}
		}
		return $aktuellesSeite;
	}

	/**
	 * Liefert alle Beiträge, die auf dieser Seite ausgegeben werden können.
	 * @return PageArray
	 */
	public function getBeitraege($args = array()) {
		$rueckgabe = new \StdClass();
		$beitraege = new PageArray();

		if ($this->projektseite instanceof Page && $this->projektseite->id) {
			// Wenn Projektseite: Alle Untertermine des Projektes mit einbeziehen
			$beitraege->add($this->projektseite->find('template=beitrag'));
		} else {
			// Beiträge von der Hauptseite:
			$beitraege->add(wire('pages')->find('template=beitrag'));
		}

		// Global freigegebene Beiträge:
		$beitraege->add(wire('pages')->find('template=beitrag, global_ausspielen=1'));

		if (isset($args['sortieren'])) {
			$beitraege->filter('sort=' . $args['sortieren']);
		} else {
			$beitraege->filter('sort=-zeitpunkt_von');
		}

		// Filterung nach Schlagwörtern:
		if (isset($args['schlagwoerter'])) {
			if (is_string($args['schlagwoerter'])) {
				$args['schlagwoerter'] = explode(',', $args['schlagwoerter']);
			}

			if (is_array($args['schlagwoerter'])) {
				$beitraege->filter('schlagwoerter='.implode('|', $args['schlagwoerter']));
			}
		}

		// Filterung nach Freitext:
		if (isset($args['freitextsuche'])) {
			if (is_string($args['freitextsuche'])) {
				$freitext = wire('sanitizer')->text($args['freitextsuche']);
				$beitraege->filter("title|name|einleitung|inhalte.text%={$freitext}");
			}
		}

		// Original-Anzahl der Artikel ohne Limit hinterlegen:
		$rueckgabe->gesamtAnzahl = $beitraege->count;

		// Der Index des letzten Elements:
		$rueckgabe->letztesElementIndex = 0;

		// Limit und Offset einbeziehen:
		$limitStrings = array();

		if (isset($args['start'])) {
			$limitStrings[] = 'start=' . $args['start'];
			$rueckgabe->letztesElementIndex = intval($args['start']);
		} elseif (isset($args['offset'])) {
			$limitStrings[] = 'start=' . $args['offset'];
			$rueckgabe->letztesElementIndex = intval($args['offset']);
		} else {
			$limitStrings[] = 'start=0';
		}

		if (isset($args['limit']) && $args['limit'] >= 0) {
			$limitStrings[] = 'limit=' . $args['limit'];
			$rueckgabe->letztesElementIndex = $rueckgabe->letztesElementIndex + intval($args['limit']);
		} elseif (!isset($args['limit'])) {
			$limitStrings[] = 'limit=12';
			$rueckgabe->letztesElementIndex = $rueckgabe->letztesElementIndex + 12;
		}

		if (!empty($limitStrings)) {
			$beitraege->filter(implode(', ', $limitStrings));
		}

		// Gibt es noch mehr Beiträge, die nachgeladen werden können?
		$rueckgabe->hatMehr = $rueckgabe->letztesElementIndex + 1 < $rueckgabe->gesamtAnzahl;

		// $args für den ÜbersichtsseitenService aufbereiten:
		if (isset($args['zeichenLimit'])) {
			$args['limit'] = $args['zeichenLimit'];
		} else {
			unset($args['limit']);
		}
		$beitraege = $this->uebersichtsseitenService->formatieren($beitraege, $args);

		foreach ($beitraege as &$beitrag) {
			if ($beitrag->projektseite instanceof Page && $beitrag->projektseite->farbe) {
				$beitrag->farbe = $beitrag->projektseite->farbe;
			}
		}

		$rueckgabe->beitraege = $beitraege;

		return $rueckgabe;
	}

	public function getAjax() {
		$ajaxOutput = array();

		$args = wire('input')->post('args');
		if (!is_array($args)) {
			$args = array();
		}

		// Ist ein Schlagwort-Filter gesetzt?
		if (wire('input')->get('schlagwoerter')) {
			$args['schlagwoerter'] = wire('input')->get('schlagwoerter');
		}

		// Ist etwas bei der Freitextsuche eingetragen?
		if (wire('input')->get('freitextsuche')) {
			$args['freitextsuche'] = wire('input')->get('freitextsuche');
		}

		if (wire('input')->get('limit')) {
			$args['limit'] = wire('input')->get('limit');
		}

		if (wire('input')->get('start')) {
			$args['start'] = wire('input')->get('start');
		} elseif (wire('input')->get('offset')) {
			$args['start'] = wire('input')->get('offset');
		}

		$args['zeichenLimit'] = 150;
		$aktuelles = $this->getBeitraege($args);
		$ajaxOutput['gesamtAnzahl'] = $aktuelles->gesamtAnzahl;
		$ajaxOutput['hatMehr'] = $aktuelles->hatMehr;
		$ajaxOutput['letztesElementIndex'] = $aktuelles->letztesElementIndex;

		// HTML-Card für jeden Beitrag mit ausliefern:
		$ajaxOutput['beitraege'] = array();
		foreach ($aktuelles->beitraege as $beitrag) {

			$komponente = $this->addComponent('BeitragCard', ['directory' => 'bauteile', 'page' => $beitrag]);
			if ($komponente instanceof TwackNullComponent) {
				continue;
			}

			$ajaxOutput['beitraege'][] = $komponente->getAjax();
		}

		return $ajaxOutput;
	}

	protected function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
