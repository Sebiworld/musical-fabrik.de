<?php
namespace ProcessWire;

class AktuellesCarousel extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$aktuellesService = $this->getService('AktuellesService');
		$aktuelles = $aktuellesService->getBeitraege(['zeichenLimit' => 150, 'limit' => 15]);
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

		$this->aktuellesSeite = $aktuellesService->getAktuellesSeite();

		$this->addScript('swiper.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true,
			'inline' => true
        ));
        $this->addScript('legacy/swiper.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true,
			'inline' => true
        ));
	}
}
