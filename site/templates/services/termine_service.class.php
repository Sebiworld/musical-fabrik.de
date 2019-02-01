<?php
namespace ProcessWire;

/**
 * Stellt Methoden zum Auslesen von Terminen zur Verfügung
 */
class TermineService extends TwackComponent {

	protected $projektseite = false;

	public function __construct($args) {
		parent::__construct($args);

		if ($this->startsWith($this->page->template->name, 'projekt')) {
			$this->projektseite = $this->page;
		} else {
			$this->projektseite = $this->page->closest('template.name^=projekt');
		}

		$this->uebersichtsseitenService = $this->getService('UebersichtsseitenService');
	}

	public function getTermineSeite() {
		$termineSeite = wire('pages')->get('/')->children('template.name=termine_container')->first();
		if ($this->projektseite instanceof Page && $this->projektseite->id) {
			$funde = $this->projektseite->find('template.name=termine_container');
			if ($funde->count > 0) {
				$termineSeite = $funde->first();
			}
		}
		return $termineSeite;
	}

	/**
	 * Liefert alle Termine, deren Zeiträume zu einem Selektor passen
	 * @param  string $zeitraumselektor
	 * @return PageArray
	 */
	public function getTermine($args = array()) {
		$rueckgabe = new \StdClass();
		$termine = new PageArray();

		if(!is_array($args)){
			$args = [];
		}

		$termineSelectorParts = array(
			'template.name=termin'
		);
		if ($this->projektseite instanceof Page && $this->projektseite->id) {
			// Wenn Projektseite: Alle Untertermine des Projektes mit einbeziehen
			// Alle globalen Termine suchen:
			$projekteContainer = wire('pages')->get('template.name=projekte_container');
			$termineSelectorParts[] = "(has_parent!={$projekteContainer->id}), (has_parent={$this->projektseite->id})";
		}

		// Filterung nach Schlagwörtern:
		if (isset($args['schlagwoerter'])) {
			if (is_string($args['schlagwoerter'])) {
				$args['schlagwoerter'] = explode(',', $args['schlagwoerter']);
			}

			if (is_array($args['schlagwoerter'])) {
				$termineSelectorParts[] = 'schlagwoerter=' . implode('|', $args['schlagwoerter']);
			}
		}

		// Filterung nach Freitext:
		if (isset($args['freitextsuche'])) {
			if (is_string($args['freitextsuche'])) {
				$freitext = wire('sanitizer')->text($args['freitextsuche']);
				$termineSelectorParts[] = "title|name|einleitung|inhalte.text%={$freitext}";
			}
		}

		if(!empty($args['start_date'])){
			if(strtolower((string)$args['start_date']) === 'TODAY'){
				$startdatum = time();
			}else{
				$startdatum = wire('sanitizer')->date($args['start_date']);
			}

			if($startdatum > 0){
				$termineSelectorParts[] = "zeitpunkt_von>={$startdatum}";
			}
		}

		// Kriterien für die Unter-Zeiträume:
		$zeitraumSelectorParts = array('template.name=zeitraum');
		if(!empty($args['gastnutzer']) && $args['gastnutzer'] || wire('user')->isGuest()){
			$zeitraumSelectorParts[] = 'fuer_gaeste_freigegeben=1';
		}

		if(!empty($args['kategorien']) && is_array($args['kategorien'])){
			// Für Kategorien gilt eine AND-Verknüpfung:
			foreach($args['kategorien'] as $kategorie){
				$zeitraumSelectorParts[] = 'termin_kategorien=' . $kategorie;
			}
		}

		// Nur Termine zulassen, auf die der Zeitraum-Selector zutrifft:
		$zeitraumSelector = false;
		if (count($zeitraumSelectorParts) > 1) {
			$zeitraumSelector = implode(', ', $zeitraumSelectorParts);
			$termineSelectorParts[] = "zeitraeume=[{$zeitraumSelector}]";
		}

		// Sollen die Termine speziell sortiert werden?
		if (isset($args['sort'])) {
			$termineSelectorParts[] = 'sort=' . $args['sort'];
		}else if (isset($args['sortieren'])) {
			$termineSelectorParts[] = 'sort=' . $args['sortieren'];
		} else {
			$termineSelectorParts[] = 'sort=-zeitpunkt_von';
		}
		$termineSelector = implode(', ', $termineSelectorParts);

		// Original-Anzahl der Artikel ohne Limit hinterlegen:
		$rueckgabe->gesamtAnzahl = wire('pages')->count($termineSelector);

		// Der Index des letzten Elements:
		$rueckgabe->letztesElementIndex = 0;

		// Limit und Offset einbeziehen:
		if (isset($args['start'])) {
			$termineSelectorParts[] = 'start=' . $args['start'];
			$rueckgabe->letztesElementIndex = intval($args['start']);
		} elseif (isset($args['offset'])) {
			$termineSelectorParts[] = 'start=' . $args['offset'];
			$rueckgabe->letztesElementIndex = intval($args['offset']);
		} else {
			$termineSelectorParts[] = 'start=0';
		}

		if (isset($args['limit']) && $args['limit'] >= 0) {
			$termineSelectorParts[] = 'limit=' . $args['limit'];
			$rueckgabe->letztesElementIndex = $rueckgabe->letztesElementIndex + intval($args['limit']);
		} elseif (!isset($args['limit'])) {
			$termineSelectorParts[] = 'limit=12';
			$rueckgabe->letztesElementIndex = $rueckgabe->letztesElementIndex + 12;
		}

		// Gibt es noch mehr Beiträge, die nachgeladen werden können?
		$rueckgabe->hatMehr = $rueckgabe->letztesElementIndex + 1 < $rueckgabe->gesamtAnzahl;

		$termineSelector = implode(', ', $termineSelectorParts);
		foreach(wire('pages')->find($termineSelector) as $termin){

			// Nur die Zeiträume ausspielen, die auf den Zeitraum-Selector matchen (wenn angegeben):
			if ($zeitraumSelector) {
				$termin->zeitraeume = $termin->zeitraeume->filter($zeitraumSelector);
			}

			$options = array();
			if(!empty($args['zeichenLimit'])){
				$options['limit'] = $args['zeichenLimit'];
			}

			$termin = $this->uebersichtsseitenService->formatierePage($termin, $options);

			$termine->add($termin);
		}

		$rueckgabe->termine = $termine;

		return $rueckgabe;
	}

	public function getAjax(){
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
		if (wire('input')->get->text('freitextsuche')) {
			$args['freitextsuche'] = wire('input')->get->text('freitextsuche');
		}

		if (wire('input')->get->int('limit')) {
			$args['limit'] = wire('input')->get->int('limit');
		}

		if (wire('input')->get->int('start')) {
			$args['start'] = wire('input')->get->int('start');
		} elseif (wire('input')->get->int('offset')) {
			$args['start'] = wire('input')->get->int('offset');
		}

		if (wire('input')->get->text('start_date')) {
			$args['start_date'] = wire('input')->get->text('start_date');
		}

		if (wire('input')->get->text('sort')) {
			$args['sort'] = wire('input')->get->text('sort');
		}

		$args['zeichenLimit'] = 150;
		$result = $this->getTermine($args);

		$ajaxOutput['gesamtAnzahl'] = $result->gesamtAnzahl;
		$ajaxOutput['hatMehr'] = $result->hatMehr;
		$ajaxOutput['letztesElementIndex'] = $result->letztesElementIndex;

		// Sammelt alle verwendeten Kategorien, damit diese nicht jedes Mal mit allen Informationen angegeben werden müssen:
		$kategorien = new PageArray();

		// Sammelt alle verwendeten Veranstaltungsorte, damit diese nicht jedes Mal mit allen Informationen angegeben werden müssen:
		$orte = new PageArray();

		// HTML-Card für jeden Beitrag mit ausliefern:
		$ajaxOutput['termine'] = array();
		foreach ($result->termine as $termin) {
			if(!$termin->viewable()){
				continue;
			}

			$komponente = $this->addComponent('TerminCard', ['directory' => 'bauteile', 'page' => $termin]);
			if ($komponente instanceof TwackNullComponent) {
				continue;
			}

			$ajaxOutput['termine'][] = $komponente->getAjax();

			$orte->add(wire('pages')->find("template.name=ort, ort.owner.has_parent={$termin->id}, check_access=0"));

			$kategorieSelector = "template.name=termin_kategorie, termin_kategorien.owner.has_parent={$termin->id}, check_access=0";
			if(wire('user')->isGuest()){
				$kategorieSelector .= ', fuer_gaeste_freigegeben=1';
			}
			$kategorien->add(wire('pages')->find($kategorieSelector));
		}

		$ajaxOutput['kategorien'] = $kategorien->explode(['id', 'name', 'title']);
		$ajaxOutput['orte'] = $orte->explode(['id', 'name', 'title', 'adresse', 'karte.lat', 'karte.lng', 'karte.zoom']);

		return $ajaxOutput;
	}

	protected function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
