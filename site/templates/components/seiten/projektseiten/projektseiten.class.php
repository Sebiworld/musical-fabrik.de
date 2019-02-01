<?php
namespace ProcessWire;

/**
 * Wird global für alle Projektseiten angewendet (Unterseiten eingeschlossen).
 */
class Projektseiten extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->isProjektseite = false;
		$this->projektseite = $this->page;
		if ($this->projektseite->template->name != 'projekt') {
			$this->projektseite = $this->page->closest('template^=projekt');
		}
		if (!($this->projektseite instanceof Page) || !($this->projektseite->id.'')) {
			return;
		}

		$this->addGlobalParameters(['projektseite' => $this->projektseite]);
		$this->isProjektseite = true;

		if ($this->projektseite->template->hasField('info_overlay') && !empty($this->projektseite->info_overlay)) {
			$this->infoOverlay = $this->projektseite->info_overlay;
		}

		if ($this->projektseite->titelbild) {
			$this->titelbild = $this->projektseite->titelbild;
			if ($this->titelbild->ext == 'svg') {
				$this->titelbild_html = $this->getService('BildService')->getBildTag(array(
				'bild' => $this->titelbild,
				'classes' => 'sv-inhalt bg-bild',
				'ausgabeAls' => 'bg-image',
				'benutzeProgressively' => false,
				'normal' => 'original'
				));
			} else {
				$this->titelbild_html =  $this->getService('BildService')->getBildTag([
				'bild' => $this->titelbild,
				'classes' => 'sv-inhalt bg-bild',
				'ausgabeAls' => 'bg-image',
				'normal' => array(
					'width' => 1800
					),
				'sm' => array(
					'width' => 700
					)
				]);
			}
		}

		// CSS-Datei für das Projekt einbinden
		$this->ladeProjektCSS($this->projektseite->name);

		// Seitenleiste-Komponenten:
		$seitenleiste = $this->getGlobalComponent('seitenleiste');

		// Bilder-Slider:
		$seitenleiste->addComponent('BilderBox');

		// Infos zu den Aufführungen:
		$seitenleiste->addComponent('AuffuehrungenBox');

		// Teilen-Buttons:
		$seitenleiste->addComponent('TeilenBox');

		// Rahmendaten:
		$seitenleiste->addComponent('RahmendatenBox');

		// Partner:
		$seitenleiste->addComponent('SponsorenBox', ['titel' => 'Unsere Partner', 'nutzefeld' => 'partner']);

		// Förderer:
		$seitenleiste->addComponent('SponsorenBox', ['titel' => 'Unsere Förderer', 'nutzefeld' => 'foerderer']);
	}

	/**
	 * Bindet eine CSS-Datei ein (wenn vorhanden)
	 */
	public function ladeProjektCSS($cssName) {
		$cssPfad = 'assets/css/'.$cssName.'.min.css';

		if (file_exists(wire('config')->paths->templates . $cssPfad)) {
			$this->addStyle(wire('config')->urls->templates . $cssPfad, true);
			return true;
		}
		return false;
	}

	public function getAjax() {
		$output = array(
			'isProjektseite' => $this->isProjektseite
		);

		if($this->isProjektseite){
			$output['projekt'] = $this->getAjaxOf($this->projektseite);

			if($this->projektseite->titelbild){
				$output['projekt']['titelbild'] = $this->getAjaxOf($this->projektseite->titelbild);
			}

			if($this->projektseite->farbe){
				$output['farbe'] = $this->projektseite->farbe;
			}
		}

		return $output;
	}
}
