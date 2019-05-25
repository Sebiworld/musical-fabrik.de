<?php
namespace ProcessWire;

class Bildergalerie extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->bilder = array();
		if (isset($args['bilder'])) {
			$this->bilder = $args['bilder'];
		} elseif ($this->page->template->hasField('bilder') && !empty($this->page->bilder)) {
			$this->bilder = $this->page->bilder;
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		if (isset($args['beschreibung'])) {
			$this->beschreibung = $args['beschreibung'];
		} elseif ($this->page->template->hasField('text') && !empty($this->page->text)) {
			$this->beschreibung = $this->page->text;
		}

		$this->addScript(wire('config')->urls->templates . 'assets/js/bildergalerie.min.js', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/bildergalerie.legacy.min.js', true);
		// $this->addStyle(wire('config')->urls->templates . 'assets/css/bildergalerie.min.css', true, true);

		$this->typ = 'masonry';
		if ($this->page->template->hasField('bildergalerie_typ') && $this->page->bildergalerie_typ->id === 2) {
			// Slider-Ansicht
			$this->typ = 'slider';
			$this->setView('BildergalerieSlider');

			$this->sliderAlign = 'center';
			if (isset($args['sliderAlign'])) {
				$this->sliderAlign = $args['sliderAlign'];
			} elseif (isset($this->sektion) && $this->sektion) {
				// In Sektionen sollen die Slides linksbündig dargestellt werden.
				$this->sliderAlign = 'left';
			}
		} elseif ($this->page->template->hasField('bildergalerie_typ') && $this->page->bildergalerie_typ->id === 3) {
			// Gitter-Ansicht
			$this->typ = 'gitter';
			// $this->addStyle(wire('config')->urls->templates . 'assets/css/bilder-gitter.min.css', true, true);
			$this->addScript(wire('config')->urls->templates . 'assets/js/masonry.min.js', true);
			$this->addScript(wire('config')->urls->templates . 'assets/js/masonry.legacy.min.js', true);
			$this->setView('BildergalerieGitter');
		} else {
			// Standard: Masonry-Ansicht
			$this->setView('BildergalerieMasonry');
			$this->addScript(wire('config')->urls->templates . 'assets/js/masonry.min.js', true);
			$this->addScript(wire('config')->urls->templates . 'assets/js/masonry.legacy.min.js', true);
		}
	}
}
