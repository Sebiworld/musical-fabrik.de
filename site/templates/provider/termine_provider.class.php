<?php
namespace ProcessWire;

/**
 * Stellt Methoden zum Auslesen von Terminen zur Verfügung
 */
class TermineProvider extends TwackComponent {

	protected $projektseite = false;

	public function __construct($args) {
		parent::__construct($args);

		if ($this->page->template->name == 'projekt') {
			$this->projektseite = $this->page;
		} else {
			$this->projektseite = $this->page->closest('template=projekt');
		}
	}

	/**
	 * Liefert alle Termine, deren Zeiträume zu einem Selektor passen
	 * @param  string $zeitraumselektor
	 * @return PageArray
	 */
	public function getTermine($zeitraumselektor = '') {
		$projekteContainer = wire('pages')->get('template.name=projekte_container');
		$termine = wire('pages')->find('template=termin, has_parent!='.$projekteContainer->id);

		// Wenn Projektseite: Alle Untertermine des Projektes mit einbeziehen
		if ($this->projektseite instanceof Page && $this->projektseite->id) {
			$termine->add($this->projektseite->find('template=termin'));
		}

		// Selektor prüfen, passende Zeiträume finden
		if (is_string($zeitraumselektor) && !empty($zeitraumselektor)) {
			$zeitraeume = wire('pages')->find('template.name=zeitraum')->filter($zeitraumselektor);
			$termineTemp = $termine->filter('zeitraeume.id='.$zeitraeume->implode('|', 'id'));

			$termine = new WireArray();
			foreach ($termineTemp as $termin) {
				// Prüfen, ob der Termin für den Nutzer sichtbar ist:
				// if(!$termin->viewable()){
				// 	continue;
				// }
				$termin->zeitraeume = $termin->zeitraeume->filter('id='.$zeitraeume->implode('|', 'id'));
				$termine->add($termin);
			}
		}

		return $termine;
	}
}
