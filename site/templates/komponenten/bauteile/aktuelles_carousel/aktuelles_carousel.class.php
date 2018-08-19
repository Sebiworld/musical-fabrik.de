<?php
namespace ProcessWire;

class AktuellesCarousel extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$aktuellesProvider = $this->getProvider('AktuellesProvider');
		$aktuelles = $aktuellesProvider->getBeitraege(['zeichenLimit' => 150, 'limit' => 15]);
		$beitragsSeiten = $aktuelles->beitraege;
		foreach ($beitragsSeiten as $seite) {
			if ($seite->projektseite instanceof Page && $seite->projektseite->farbe) {
				$seite->farbe = $seite->projektseite->farbe;
			}
			$this->addComponent('BeitragCard', ['directory' => 'bauteile', 'page' => $seite]);
		}

		$this->sliderAlign = 'left';
		if (isset($args['sliderAlign'])) {
			$this->sliderAlign = $args['sliderAlign'];
		} elseif (isset($this->sektion) && $this->sektion) {
			// In Sektionen sollen die Slides linksbündig dargestellt werden.
			$this->sliderAlign = 'left';
		}

		$this->aktuellesSeite = $aktuellesProvider->getAktuellesSeite();

		$this->addStyle(wire('config')->urls->templates . 'assets/css/aktuelles-carousel.min.css', true, true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/swiper.min.js', true, true);
	}
}
