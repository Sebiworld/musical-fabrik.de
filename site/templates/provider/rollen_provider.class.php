<?php
namespace ProcessWire;

/**
 * Stellt Methoden zum Auslesen von Beiträgen
 */
class RollenProvider extends TwackComponent {

	protected $projektseite;
	protected $portraitsContainer;

	public function __construct($args) {
		parent::__construct($args);

		$this->projektseite = $this->page;
		if ($this->projektseite->template->name != 'projekt') {
			$this->projektseite = $this->page->closest('template=projekt');
		}
		if (isset($args['projektseite']) && $args['projektseite'] instanceof Page && $args['projektseite']->id) {
			$this->projektseite = $args['projektseite'];
		}
		if (!($this->projektseite instanceof Page) || !$this->projektseite->id) {
			$this->projektseite = wire('pages')->get('/');
		}

		$this->portraitsContainer = wire('pages')->find('template.name=portraits_container, include=hidden, has_parent='.$this->projektseite->id);
		if ($this->portraitsContainer->count < 1) {
			throw new ComponentNotInitialisedException('RollenProvider', 'Es wurden keine passenden Portraits-Container gefunden.');
		}
	}

	/**
	 * Liefert die Rolle und Unterrollen mit Portraits
	 * @return StdClass
	 */
	public function getRolle($rolleSeite = false, $anzeigeModus = false, $ebenen = 2) {
		$ersterAufruf = false;
		if ($rolleSeite === false && $anzeigeModus === false) {
			// Oberste Rolle. Wenn kein Anzeigemodus gesetzt ist, soll der des Elternelements genommen werden.
			$elternElement = $this->page->closest('template.name=rollen_container|rolle, rolle_anzeige_optionen!=""');
			$ersterAufruf = true;
			if ($elternElement instanceof Page && $elternElement->id && $elternElement->template->hasField('rolle_anzeige_optionen') && $elternElement->rolle_anzeige_optionen->name) {
				$anzeigeModus = $elternElement->rolle_anzeige_optionen->name;
			}
		}
		if ($anzeigeModus === false) {
			$anzeigeModus = 'als_block';
		}

		// Wenn keine Rolle vorgegeben wird, wird die aktuelle Seite als Basis-Rolle genommen:
		if (!$rolleSeite || !($rolleSeite instanceof Page) || !$rolleSeite->id) {
			$rolleSeite = $this->page;
		}

		$staffelnContainer = $this->projektseite->get('template.name=staffeln_container');

		// Anzeigemodus für diese Rolle bestimmen. Wenn nicht explizit gesetzt, wird der Wert des Elternelements genommen.
		if ($rolleSeite->template->hasField('rolle_anzeige_optionen') && $rolleSeite->rolle_anzeige_optionen->name) {
			$anzeigeModus = $rolleSeite->rolle_anzeige_optionen->name;
		}

		$rollenAusgabe = array();
		$rollenAusgabe['seite'] = $rolleSeite;
		$rollenAusgabe['anzeigeModus'] = $anzeigeModus;
		if ($ersterAufruf) {
			$rollenAusgabe['obersteEbene'] = true;
		}

		// Anzeige nur Gruppenbild: Es wird nur diese Rolle ohne Portraits benötigt
		if ($anzeigeModus == 'nur_gruppenbild') {
			return $rollenAusgabe;
		}

		if ($ebenen < 1) {
			return $rollenAusgabe;
		}

		$ebenen = $ebenen - 1;
		// Wenn Anzeige nur Unterrollen-Teaser: Nur die direkten Kind-Rollen anteasern, ohne Portraits:
		if ($anzeigeModus == 'unterrollen_teaser') {
			$ebenen = 0;
		}
		// else if($anzeigeModus == 'als-block-mit-rollen' && $ebenen > 1) $ebenen = 1;

		// Kind-Rollen ermitteln und die Einstellungen und Portraits dafür holen:
		$rollen = array();
		foreach ($rolleSeite->children('template.name=rolle') as $kindrolle) {
			$rollen[] = $this->getRolle($kindrolle, $anzeigeModus, $ebenen);
		}
		$rollenAusgabe['rollen'] = $rollen;

		if ($anzeigeModus == 'als_block' || $anzeigeModus == 'als_block_mit_rollen') {
			// Für die Blockanzeige werden alle Portraits der Rolle benötigt

			$rollenAusgabe['portraits'] = new PageArray();
			$rollenAusgabe['staffeln'] = array();
			foreach ($rolleSeite->darsteller as $darstellereintrag) {
				if ($darstellereintrag->type !== 'staffel' && $darstellereintrag->type !== 'besetzung_staffel') {
					$rollenAusgabe['portraits']->add($darstellereintrag->portraits);
					continue;
				}

				// Dieser Eintrag gilt für eine bestimmte Staffel
				foreach ($darstellereintrag->staffeln as $staffel) {
					if (!isset($rollenAusgabe['staffeln'][$staffel->id]) || !is_array($rollenAusgabe['staffeln'][$staffel->id])) {
						$rollenAusgabe['staffeln'][$staffel->id] = array(
							'id' => $staffel->id,
							'name' => $staffel->name,
							'title' => $staffel->title,
							'portraits' => new PageArray()
						);
					}

					$rollenAusgabe['staffeln'][$staffel->id]['portraits']->add($darstellereintrag->portraits);
				}
			}

			// Alle Portraits ohne Staffel-Zugehörigkeit müssen in die Staffeln einsortiert werden:
			if(!empty($rollenAusgabe['staffeln']) && !empty($rollenAusgabe['portraits'])){
				foreach($rollenAusgabe['staffeln'] as $staffel){
					$staffel['portraits']->add($rollenAusgabe['portraits']);
				}

				// Die Staffeln, die vielleicht noch nicht vorhanden sind, hinzufügen:
				foreach($staffelnContainer->children('id!=' . implode('|', array_keys($rollenAusgabe['staffeln']))) as $staffel){
					if (!isset($rollenAusgabe['staffeln'][$staffel->id]) || !is_array($rollenAusgabe['staffeln'][$staffel->id])) {
						$rollenAusgabe['staffeln'][$staffel->id] = array(
							'id' => $staffel->id,
							'name' => $staffel->name,
							'title' => $staffel->title,
							'portraits' => new PageArray()
						);
					}

					$rollenAusgabe['staffeln'][$staffel->id]['portraits'] = $rollenAusgabe['portraits'];
				}
			}

		} elseif ($anzeigeModus == 'nach_besetzungen' || $anzeigeModus == 'als_besetzung_block') {
			// Für die Anzeige nach Besetzungen werden pro Besetzung die Portraits sowie die jeweilige Besetzungs-Seite benötigt:
			$portraits = new PageArray();
			foreach ($this->portraitsContainer as $container) {
				$portraits->add(wire('pages')->find('template.name=portrait, sort=nachname, rollen.rolle.id='.$rolleSeite->id));
			}

			$rollenAusgabe['besetzungen'] = array();

			// Sammlung aller Staffeln, die in den Besetzungen gefunden werden:
			$rollenAusgabe['staffeln'] = new PageArray();

			$esGibtPortraits = false;
			foreach ($this->getBesetzungen() as $besetzung) {
				$besetzungArray = array();
				$besetzungArray['seite'] = $besetzung;
				$besetzungArray['portraits'] = new PageArray();

				if ($besetzung->staffeln instanceof PageArray && $besetzung->staffeln->count > 0) {
					$rollenAusgabe['staffeln']->add($besetzung->staffeln);
				}

				foreach ($portraits as $portrait) {
					$rollenInput = $portrait->rollen->get('rolle='.$rolleSeite->id);
					if (!($rollenInput instanceof Rolle)) {
						continue;
					}
					if (!$rollenInput->besetzungen->get('id='.$besetzung->id)) {
						continue;
					}
					$besetzungArray['portraits']->add($portrait);
					$esGibtPortraits = true;
				}

				$rollenAusgabe['besetzungen'][] = $besetzungArray;
			}

			if ($rollenAusgabe['staffeln']->count > 0) {
				// Sortierung wie im Seitenbaum anwenden:
				$staffelnTmp = $rollenAusgabe['staffeln'];
				$rollenAusgabe['staffeln'] = $staffelnContainer->children('id=' . implode('|', array_keys($rollenAusgabe['staffeln'])));
			}

			if (!$esGibtPortraits && !$ersterAufruf) {
				unset($rollenAusgabe['besetzungen']);
			}
		}

		return $rollenAusgabe;
	}

	/**
	 * Liefert die verfügbaren Besetzungen
	 * @return PageArray
	 */
	public function getBesetzungen() {
		$container = $this->projektseite->find('template.name=besetzungen_container, include=hidden');
		$seiten = new PageArray();
		if ($container instanceof PageArray && count($container) > 0) {
			foreach ($container as $seite) {
				$seiten->add($seite->children('template.name=besetzung'));
			}
		}
		return $seiten;
	}

	/**
	 * Liefert die Anzahl an Mitwirkenden für eine Rollenseite
	 * @param  Page $rolleSeite
	 * @return
	 */
	public function getMitwirkendenAnzahl($rolleSeite = false) {
		if (!($rolleSeite instanceof Page) || !$rolleSeite->id) {
			$rolleSeite = wire('pages')->get('/');
		}

		$unterrollenOhneAnzahl = $rolleSeite->find('template.name=rolle, anzahl=""');
		$anzahl = wire('pages')->find('template.name=portrait, rollen.rolle.parent='.$unterrollenOhneAnzahl->implode('|', 'id'))->count;

		foreach ($rolleSeite->find('template.name=rolle, anzahl>0') as $unterrolleMitAnzahl) {
			$anzahl += $unterrolleMitAnzahl->anzahl;
		}

		return $anzahl;
	}
}
