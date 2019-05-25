<?php
namespace ProcessWire;

class OnepageSeitenGrid extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->bildService = $this->getService('BildService');

		// Die ID der Onepage-Sektion ermitteln:
		$this->sektionID = '';
		if ((string) $this->page->sektion_name) {
			$this->sektionID = (string) $this->page->sektion_name;
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		if ($this->page->template->hasField('inhalte')) {
			$this->inhalte = $this->addComponent('Inhalte', ['directory' => '', 'page' => $this->page]);
		}

		// Wenn kein spezielles Feld übergeben wurde: Feldname "seiten" benutzen
		if (!isset($args['nutzeFeld'])) {
			$args['nutzeFeld'] = 'seiten';
		}

		if (!$this->page->template->hasField($args['nutzeFeld'])) {
			throw new ComponentNotInitializedException("OnepageSeitenGrid", "Es wurde kein benutzbares Seiten-Feld übergeben.");
		}

		$this->seiten = new PageArray();

		$this->cardKlasse = '';
		if ($this->page->template->hasField('card_overlay') && $this->page->card_overlay && $this->page->card_overlay->title) {
			$this->cardKlasse = 'overlay ' . $this->page->card_overlay->title;
		}

		$this->bildFormat = '1-1';
		if ($this->page->template->hasField('bild_format') && $this->page->bild_format && $this->page->bild_format->title) {
			$this->bildFormat = $this->page->bild_format->title;
		}

		$this->gridKlassenBestimmen();
		$this->feldEinlesen($this->page->fields->get($args['nutzeFeld']));

		// $this->addStyle(wire('config')->urls->templates . 'assets/css/sektion-seiten-grid.min.css', true, true);
	}

	protected function feldEinlesen(Field $feld) {
		if ($feld->type instanceof FieldtypeMulti) {
			$werte = $this->page->get($feld->name);
			if ($werte instanceof PageArray) {
				$this->seitenHinzufuegen($werte);
			} elseif ($werte instanceof Page) {
				$array = new PageArray();
				$array->add($werte);
				$this->seitenHinzufuegen($array);
			} else {
				Twack::devAusgabe('OnepageSeitenGrid->feldEinlesen() konnte keine ausgebbaren Seiten auslesen.');
			}
		}
	}

	protected function gridKlassenBestimmen() {
		// Bootstrap-Grid-String bestimmen:
		$this->cardGroesse = '3';
		if ($this->page->template->hasField('card_groesse') && $this->page->card_groesse && $this->page->card_groesse->title) {
			$this->cardGroesse = $this->page->card_groesse->title;
		}

		$this->gridKlassen = '';
		$cardgroessen = [1, 2, 3, 4, 6, 12];
		$groesseKuerzel = ['xl', 'lg', 'md', 'sm', 'xs'];

		foreach ($cardgroessen as $key => $groesse) {
			if ($groesse < $this->cardGroesse) {
				unset($cardgroessen[$key]);
				continue;
			}
			break;
		}

		foreach ($groesseKuerzel as $key => $kuerzel) {
			$groesse = array_shift($cardgroessen);
			if ($groesse === null) {
				break;
			}

			if (!empty($this->gridKlassen)) {
				$this->gridKlassen = ' ' . $this->gridKlassen;
			}
			$this->gridKlassen = 'col-' . $kuerzel . '-' . $groesse . $this->gridKlassen;

			unset($groesseKuerzel[$key]);
		}

		if (empty($cardgroessen)) {
			$this->gridKlassen = 'col-12 ' . $this->gridKlassen;
		} elseif (empty($cardgroessen)) {
			$this->gridKlassen = 'col-' . $cardgroessen[0] . $this->gridKlassen;
		}
	}

	/**
	 * Fügt ein Array aus Seiten hinzu
	 * @param  PageArray $seiten
	 */
	protected function seitenHinzufuegen(PageArray $seiten) {
		foreach ($seiten as $seite) {
			$this->seiteHinzufuegen($seite);
		}
	}

	/**
	 * Fügt eine einzelne Seite hinzu
	 * @param  Page   $seite
	 */
	protected function seiteHinzufuegen(Page $seite) {
		// Twack::devEcho($seite->name, $seite->viewable());
		if (!$seite->viewable()) {
			return false;
		}

		if ($seite->hasField('logo_quadrat')) {
			$seite->gridbild = $seite->logo_quadrat;
		} else {
			$seite->gridbild = $seite->titelbild;
		}

		if ($seite->hasField('kurzbeschreibung')) {
			$seite->beschreibung = $seite->kurzbeschreibung;
		} elseif ($seite->hasField('freitext')) {
			$seite->beschreibung = $seite->freitext;
		}

		$this->seiten->add($seite);
		return $seite;
	}
}
